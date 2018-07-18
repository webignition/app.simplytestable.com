<?php
namespace AppBundle\Command\Reporting;

use Doctrine\ORM\EntityManagerInterface;
use AppBundle\Entity\Task\Output;
use AppBundle\Repository\TaskOutputRepository;
use AppBundle\Services\TaskTypeService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\Task\Output as TaskOutput;
use AppBundle\Entity\Task\Type\Type as TaskType;

class GetOutputIdsForErrorCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_MISSING_TASK_TYPE = 1;
    const RETURN_CODE_INVALID_TASK_TYPE = 2;
    const RETURN_CODE_MISSING_FRAGMENTS = 3;

    /**
     * @var TaskTypeService
     */
    private $taskTypeService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TaskOutputRepository
     */
    private $taskOutputRepository;

    /**
     * @param TaskTypeService $taskTypeService
     * @param EntityManagerInterface $entityManager
     * @param string|null $name
     */
    public function __construct(
        TaskTypeService $taskTypeService,
        EntityManagerInterface $entityManager,
        $name = null
    ) {
        parent::__construct($name);

        $this->taskTypeService = $taskTypeService;
        $this->entityManager = $entityManager;
        $this->taskOutputRepository = $entityManager->getRepository(Output::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:reporting:get-output-ids-for-error')
            ->setDescription('Get list of task output ids matching certain error messages')
            ->addOption(
                'task-type',
                null,
                InputOption::VALUE_REQUIRED,
                'Name of task type for which to generate report'
            )
            ->addOption(
                'task-output-limit',
                null,
                InputOption::VALUE_OPTIONAL,
                'Limit the number of task outputs processed'
            )
            ->addOption(
                'task-output-offset',
                null,
                InputOption::VALUE_OPTIONAL,
                'Offset for task output list'
            )
            ->addOption(
                'fragments',
                null,
                InputOption::VALUE_REQUIRED,
                'Fragments of errors to match against'
            )
            ->addOption(
                'output-only-ids',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output only a comma-separated list of matching task output ids'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $outputOnlyIds = filter_var($input->getOption('output-only-ids'), FILTER_VALIDATE_BOOLEAN);

        $taskTypeName = $input->getOption('task-type');
        $taskType = null;

        if (empty($taskTypeName)) {
            if (!$outputOnlyIds) {
                $output->writeln('<error>Required option "task-type" missing</error>');
            }

            return self::RETURN_CODE_MISSING_TASK_TYPE;
        }

        if (!$outputOnlyIds) {
            $output->write('<info>Requested task type: "' . $taskTypeName . '" ... </info>');
        }

        $taskType = $this->taskTypeService->get($taskTypeName);

        if (empty($taskType)) {
            if (!$outputOnlyIds) {
                $output->writeln('<error>invalid</error>');
            }

            return self::RETURN_CODE_INVALID_TASK_TYPE;
        }

        if (!$outputOnlyIds) {
            $output->writeln('<comment>ok</comment>');
            $output->write('<info>Fragments to match: ... </info>');
        }

        $fragments = $this->getFragments($input);

        if (empty($fragments)) {
            if (!$outputOnlyIds) {
                $output->writeln('<comment>none specified, stopping</comment>');
            }

            return self::RETURN_CODE_MISSING_FRAGMENTS;
        }

        $limit = (int)$input->getOption('task-output-limit');
        $limit = $limit < 0 ? null : $limit;

        $offset = (int)$input->getOption('task-output-offset');
        $offset = $offset < 0 ? null : $offset;

        if (!$outputOnlyIds) {
            $output->writeln('<comment>' . implode(', ', $fragments) . '</comment>');
            $output->write('<info>Requested limit: ');
            $output->writeln('<comment>' . (empty($limit) ? 'NONE' : $limit) . '</comment>');
            $output->write('<info>Requested offset: ');
            $output->writeln('<comment>' . (empty($offset) ? 'NONE' : $offset) . '</comment>');
            $output->writeln('');
            $output->write('Finding task output for [' . $taskTypeName . '] tasks ... ');
        }

        $taskOutputIds = $this->taskOutputRepository->findIdsByTaskType($taskType, $limit, $offset);

        if (!$outputOnlyIds) {
            $output->writeln('[' . count($taskOutputIds) . '] task outputs found');
        }

        $processedTaskOutputCount = 0;
        $outputIds = [];

        foreach ($taskOutputIds as $taskOutputId) {
            $processedTaskOutputCount++;

            if (!$outputOnlyIds) {
                $output->write('.');
            }

            /* @var Output $taskOutput */
            $taskOutput = $this->taskOutputRepository->find($taskOutputId);
            $messages = $this->getMessagesForTaskOutput($taskOutput, $taskType);

            foreach ($messages as $message) {
                if ($this->isFragmentMatch($message, $fragments)) {
                    if (!in_array($taskOutputId, $outputIds)) {
                        $outputIds[] = $taskOutputId;
                    }
                }
            }

            $this->entityManager->detach($taskOutput);
        }

        if ($outputOnlyIds) {
            $output->writeln(implode(',', $outputIds));
        } else {
            $output->writeln('');
            $output->writeln('<info>============================================</info>');
            $output->writeln('');
            $output->writeln('Outputs found: ' . count($outputIds));
            $output->writeln('');
            $output->writeln(implode(',', $outputIds));
            $output->writeln('');
        }

        return self::RETURN_CODE_OK;
    }

    /**
     * @param string $message
     * @param array $fragments
     *
     * @return bool
     */
    private function isFragmentMatch($message, $fragments)
    {
        $message = strtolower($message);

        foreach ($fragments as $fragment) {
            if (!substr_count($message, $fragment)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param InputInterface $input
     *
     * @return string[]
     */
    private function getFragments(InputInterface $input)
    {
        $fragmentsString = $input->getOption('fragments');

        if (empty($fragmentsString)) {
            return [];
        }

        $fragments = explode(',', $fragmentsString);

        array_walk($fragments, function (&$value) {
            $value = trim(strtolower($value));
        });

        return $fragments;
    }

    /**
     * @param TaskOutput $taskOutput
     * @param TaskType $taskType
     *
     * @return string[]
     */
    private function getMessagesForTaskOutput(TaskOutput $taskOutput, TaskType $taskType)
    {
        $messages = [];

        if ($taskOutput->getErrorCount() === 0) {
            return $messages;
        }

        switch ($taskType->getName()) {
            case TaskTypeService::HTML_VALIDATION_TYPE:
                $decodedOutput = json_decode($taskOutput->getOutput(), true);

                if (is_array($decodedOutput)) {
                    foreach ($decodedOutput['messages'] as $message) {
                        if ($message['type'] === 'error') {
                            $messages[] = $message['message'];
                        }
                    }
                }

                break;
        }

        return $messages;
    }
}
