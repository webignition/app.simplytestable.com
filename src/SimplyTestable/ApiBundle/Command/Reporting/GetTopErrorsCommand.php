<?php
namespace SimplyTestable\ApiBundle\Command\Reporting;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Task\Output;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\ApiBundle\Entity\Task\Output as TaskOutput;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use webignition\HtmlValidationErrorNormaliser\HtmlValidationErrorNormaliser;
use webignition\HtmlValidationErrorNormaliser\Result as HtmlValidationErrorNormaliserResult;

class GetTopErrorsCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_MISSING_TASK_TYPE = 1;
    const RETURN_CODE_INVALID_TASK_TYPE = 2;
    const DEFAULT_REPORT_LIMIT = 100;

    /**
     * @var TaskTypeService
     */
    private $taskTypeService;

    /**
     * @var EntityManager
     */
    private $entityManager;


    private $messages = [];

    /**
     * @param TaskTypeService $taskTypeService
     * @param EntityManager $entityManager
     * @param string|null $name
     */
    public function __construct(
        TaskTypeService $taskTypeService,
        EntityManager $entityManager,
        $name = null
    ) {
        parent::__construct($name);

        $this->taskTypeService = $taskTypeService;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:maintenance:get-top-errors')
            ->setDescription('Generate top errors report by task type')
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
                'report-limit',
                null,
                InputOption::VALUE_OPTIONAL,
                'Limit the number lines in the report'
            )
            ->addOption(
                'type-filter',
                null,
                InputOption::VALUE_OPTIONAL,
                'Filter to normalised only (N) or non-normalised only (R)'
            )
            ->addOption(
                'normalise',
                null,
                InputOption::VALUE_OPTIONAL,
                'Normalise error messages to common form?'
            )
            ->addOption(
                'report-only',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output report only, no errors or meta data'
            )
            ->addOption(
                'error-only',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output errors only, no counts'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $reportOnly = filter_var($input->getOption('report-only'), FILTER_VALIDATE_BOOLEAN);
        $errorOnly = filter_var($input->getOption('error-only'), FILTER_VALIDATE_BOOLEAN);
        $shouldNormalise = filter_var($input->getOption('normalise'), FILTER_VALIDATE_BOOLEAN);
        $taskTypeName = $input->getOption('task-type');

        $taskOutputLimit = (int)$input->getOption('task-output-limit');
        $taskOutputLimit = $taskOutputLimit < 0 ? null : $taskOutputLimit;

        $taskOutputOffset = (int)$input->getOption('task-output-offset');
        $taskOutputOffset = $taskOutputOffset < 0 ? null : $taskOutputOffset;

        $reportLimit = (int)$input->getOption('report-limit');
        $reportLimit = $taskOutputOffset <= 0 ? self::DEFAULT_REPORT_LIMIT : $reportLimit;

        $typeFilter = $input->getOption('type-filter');

        $taskType = null;

        if (empty($taskTypeName)) {
            if (!$reportOnly) {
                $output->writeln('<error>Required option "task-type" missing</error>');
            }

            return self::RETURN_CODE_MISSING_TASK_TYPE;
        }

        if (!$reportOnly) {
            $output->write('<info>Requested task type: ' . $taskTypeName . ' ... </info>');
        }

        $taskType = $this->taskTypeService->getByName($taskTypeName);

        if (empty($taskType)) {
            if (!$reportOnly) {
                $output->writeln('<error>invalid</error>');
            }

            return self::RETURN_CODE_INVALID_TASK_TYPE;
        }

        if (!$reportOnly) {
            $output->writeln('<comment>ok</comment>');
        }

        if (!$reportOnly) {
            $output->write('<info>Requested limit: ');
            $output->writeln('<comment>' . (empty($taskOutputLimit) ? 'NONE' : $taskOutputLimit) . '</comment>');
            $output->write('<info>Requested offset: ');
            $output->writeln('<comment>' . (empty($taskOutputOffset) ? 'NONE' : $taskOutputOffset) . '</comment>');
            $output->writeln('');
            $output->write('Finding task output for [' . $taskTypeName . '] tasks ... ');
        }

        $taskOutputRepository = $this->entityManager->getRepository(Output::class);
        $taskOutputIds = $taskOutputRepository->findIdsByTaskType($taskType, $taskOutputLimit, $taskOutputOffset);

        $taskOutputCount = count($taskOutputIds);

        if (!$reportOnly) {
            $output->writeln('[' . $taskOutputCount . '] task outputs found');
        }

        $processedTaskOutputCount = 0;
        $messageCount = 0;

        foreach ($taskOutputIds as $taskOutputId) {
            $processedTaskOutputCount++;

            if (!$reportOnly) {
                $output->writeln(sprintf(
                    'Processing task output [%s] [%s of %s]',
                    $taskOutputId,
                    $processedTaskOutputCount,
                    $taskOutputCount
                ));
            }

            $taskOutput = $taskOutputRepository->find($taskOutputId);

            $messages = $this->getMessagesForTaskOutput($taskOutput, $taskType);

            foreach ($messages as $message) {
                $messageCount++;

                $normalisationResult = new HtmlValidationErrorNormaliserResult();

                if ($shouldNormalise) {
                    $normaliser = new HtmlValidationErrorNormaliser();

                    /* @var HtmlValidationErrorNormaliserResult $normalisationResult */
                    $normalisationResult = $normaliser->normalise($message);
                    $messageToStore = $normalisationResult->isNormalised()
                        ? trim($normalisationResult->getNormalisedError()->getNormalForm())
                        : trim($message);

                } else {
                    $messageToStore = trim($message);
                }

                if (!array_key_exists($messageToStore, $this->messages)) {
                    $this->messages[$messageToStore] = [
                        'count' => 0,
                        'normalised' => ($shouldNormalise)
                            ? $normalisationResult->isNormalised()
                            : false
                    ];

                    if ($shouldNormalise && $normalisationResult->isNormalised()) {
                        $this->messages[$messageToStore]['parameters'] = [];
                    }
                }

                $this->messages[$messageToStore]['count']++;

                if ($shouldNormalise && $normalisationResult->isNormalised()) {
                    $parameterValues = [];

                    $normalisedMessageParameters = $normalisationResult->getNormalisedError()->getParameters();
                    $normalisedMessageParameterCount = count($normalisedMessageParameters);

                    foreach ($normalisedMessageParameters as $position => $value) {
                        $parameterValues[] = $value;
                        $parameterStore = $this->getParameterStore(
                            $parameterValues,
                            $messageToStore,
                            $normalisedMessageParameterCount
                        );

                        $parameterStore['count']++;
                        $this->setParameterStore($parameterValues, $messageToStore, $parameterStore);
                    }
                }
            }

            $this->entityManager->detach($taskOutput);
        }

        if (!$reportOnly) {
            $output->writeln('');
            $output->writeln('<info>============================================</info>');
            $output->writeln('');
            $output->writeln('Total messages analysed: ' . $messageCount);
            $output->writeln('');
        }

        $this->sortMessages();

        $this->messages = array_slice($this->messages, 0, $reportLimit);

        $reportData = [];

        foreach ($this->messages as $message => $messageStatistics) {
            if ($this->includeMessageInReport($messageStatistics, $typeFilter) === false) {
                continue;
            }

            if ($shouldNormalise) {
                $reportItem = new \stdClass();
                $reportItem->count = $messageStatistics['count'];
                $reportItem->normal_form = $message;

                if (isset($messageStatistics['parameters'])) {
                    $reportItem->parameters = $messageStatistics['parameters'];
                }

                $reportData[] = $reportItem;
            } else {
                if ($errorOnly) {
                    if (substr_count($message, "\n") === 0) {
                        $output->writeln($message);
                    }
                } else {
                    $output->writeln($messageStatistics['count'] . "\t" . $message);
                }
            }

        }

        if ($shouldNormalise) {
            $output->writeln(json_encode($reportData));
        }

        return self::RETURN_CODE_OK;
    }

    /**
     * @param string[] $parameterValues
     * @param string $messageToStore
     * @param int $parameterCount
     *
     * @return array
     */
    private function getParameterStore($parameterValues, $messageToStore, $parameterCount)
    {
        $messageStoreParameters = $this->messages[$messageToStore]['parameters'];

        switch (count($parameterValues)) {
            case 1:
                $key0 = $parameterValues[0];

                if (!isset($messageStoreParameters[$key0])) {
                    $this->setParameterStore($parameterValues, $messageToStore, ['count' => 0]);
                }

                break;

            case 2:
                $key0 = $parameterValues[0];
                $key1 = $parameterValues[1];

                if (!isset($messageStoreParameters[$key0]['children'][$key1])) {
                    $this->setParameterStore($parameterValues, $messageToStore, ['count' => 0]);
                }

                break;

            case 3:
                $key0 = $parameterValues[0];
                $key1 = $parameterValues[1];
                $key2 = $parameterValues[2];

                if (!isset($messageStoreParameters[$key0]['children'][$key1]['children'][$key2])) {
                    $this->setParameterStore($parameterValues, $messageToStore, ['count' => 0]);
                }

                break;

            case 4:
                $key0 = $parameterValues[0];
                $key1 = $parameterValues[1];
                $key2 = $parameterValues[2];
                $key3 = $parameterValues[3];

                if (!isset($messageStoreParameters[$key0]['children'][$key1]['children'][$key2]['children'][$key3])) {
                    $this->setParameterStore($parameterValues, $messageToStore, ['count' => 0]);
                }

                break;
        }

        return $this->getMessageStoreForParameterValuesAndMessage($parameterValues, $messageToStore);
    }

    /**
     * @param string[] $parameterValues
     * @param string $messageToStore
     *
     * @return mixed
     */
    private function getMessageStoreForParameterValuesAndMessage($parameterValues, $messageToStore)
    {
        $count = count($parameterValues);

        $messageStoreParameters = $this->messages[$messageToStore]['parameters'];
        $current = $messageStoreParameters;

        for ($i = 0; $i < $count; $i++) {
            if ($i === $count -1) {
                $current = $current[$parameterValues[$i]];
            } else {
                $current = $current[$parameterValues[$i]]['children'];
            }
        }

        return $current;
    }

    /**
     * @param string[] $parameterValues
     * @param string $message
     * @param array $parameterStore
     */
    private function setParameterStore($parameterValues, $message, $parameterStore)
    {
        switch (count($parameterValues)) {
            case 1:
                $k0 = $parameterValues[0];

                $this->messages[$message]['parameters'][$k0] = $parameterStore;
                break;

            case 2:
                $k0 = $parameterValues[0];
                $k1 = $parameterValues[1];

                $this->messages[$message]['parameters'][$k0]['children'][$k1] = $parameterStore;
                break;

            case 3:
                $k0 = $parameterValues[0];
                $k1 = $parameterValues[1];
                $k2 = $parameterValues[2];

                $this->messages[$message]['parameters'][$k0]['children'][$k1]['children'][$k2] =
                    $parameterStore;
                break;

            case 4:
                $k0 = $parameterValues[0];
                $k1 = $parameterValues[1];
                $k2 = $parameterValues[2];
                $k3 = $parameterValues[3];

                $this->messages[$message]['parameters'][$k0]['children'][$k1]['children'][$k2]['children'][$k3] =
                    $parameterStore;
                break;
        }
    }

    /**
     * @param array $messageStatistics
     * @param string $typeFilter
     *
     * @return bool
     */
    private function includeMessageInReport($messageStatistics, $typeFilter)
    {
        if (empty($typeFilter)) {
            return true;
        }

        if ($typeFilter === 'N' && $messageStatistics['normalised'] === true) {
            return true;
        }

        if ($typeFilter === 'R' && $messageStatistics['normalised'] === false) {
            return true;
        }

        return false;
    }

    private function sortMessages()
    {
        $frequencyIndex = [];

        foreach ($this->messages as $message => $messageStatistics) {
            $frequencyIndex[$message] = $messageStatistics['count'];
        }

        arsort($frequencyIndex);

        $messages = [];

        foreach ($frequencyIndex as $message => $count) {
            $messages[$message] = $this->messages[$message];

            if (isset($messages[$message]['parameters'])) {
                $messages[$message]['parameters'] = $this->sortMessageParameters($messages[$message]['parameters']);
            }
        }

        $this->messages = $messages;
    }

    /**
     * @param array $parameters
     *
     * @return array
     */
    private function sortMessageParameters($parameters)
    {
        $index = [];
        foreach ($parameters as $value => $properties) {
            if (isset($properties['children'])) {
                $parameters[$value]['children'] = $this->sortMessageParameters($properties['children']);
            }

            $index[$value] = $properties['count'];
        }

        arsort($index);

        $sortedParameters = [];

        foreach ($index as $value => $count) {
            $sortedParameters[$value] = $parameters[$value];
        }

        return $sortedParameters;
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
