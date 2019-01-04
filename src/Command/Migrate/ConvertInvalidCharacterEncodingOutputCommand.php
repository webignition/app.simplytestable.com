<?php

namespace App\Command\Migrate;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Task\Output;
use App\Services\ApplicationStateService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @TODO Remove in #367
 */
class ConvertInvalidCharacterEncodingOutputCommand extends Command
{
    const DEFAULT_FLUSH_THRESHOLD = 100;

    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;

    /**
     * @var ApplicationStateService
     */
    private $applicationStateService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param ApplicationStateService $applicationStateService
     * @param EntityManagerInterface $entityManager
     * @param string|null $name
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        EntityManagerInterface $entityManager,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:migrate:convert-invalid-character-encoding-output')
            ->setDescription('Convert invalid-character-encoding output to new format')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED)
            ->addOption('flush-threshold', 'f', InputOption::VALUE_OPTIONAL, '', 10)
            ->addOption('dry-run');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->applicationStateService->isInReadOnlyMode()) {
            $output->writeln('In maintenance-read-only mode, I can\'t do that right now');
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $isDryRun = $input->getOption('dry-run');
        if ($isDryRun) {
            $output->writeln('<comment>This is a DRY RUN, no data will be written</comment>');
        }

        $limit = (int) $input->getOption('limit');
        $flushThreshold = (int) $input->getOption('flush-threshold');

        $output->writeln([
            '',
            sprintf('<comment>Limit:</comment> %s', $limit),
            sprintf('<comment>Flush threshold:</comment> %s', $flushThreshold),
            '',
        ]);

        $output->write('Finding old invalid character encoding output ... ');

        $taskOutputRepository = $this->entityManager->getRepository(Output::class);
        $taskOutputs = $taskOutputRepository->findOutputContainingOldInvalidCharacterEncodingError($limit);

        $output->writeln(
            sprintf('<comment>%s</comment> found.', count($taskOutputs))
        );

        $persistCount = 0;

        foreach ($taskOutputs as $taskOutput) {
            $output->writeln(
                sprintf('Processing output %s', $taskOutput->getId())
            );

            $characterSet = $this->getCharacterEncodingFromOldInvalidCharacterEncodingOutput($taskOutput);

            $updatedOutput = [
                'messages' => [
                    [
                        'message' => $characterSet,
                        'messageId' => 'invalid-character-encoding',
                        'type' => 'error',
                    ],
                ],
            ];

            $taskOutput->setOutput(json_encode($updatedOutput));
            $taskOutput->generateHash();

            $persistCount++;

            if ($persistCount == $flushThreshold) {
                $output->writeln('***** Flushing *****');
                $persistCount = 0;

                if (!$isDryRun) {
                    $this->entityManager->flush();
                }
            }
        }

        if ($persistCount > 0) {
            $output->writeln('***** Flushing *****');
            if (!$isDryRun) {
                $this->entityManager->flush();
            }
        }

        $output->writeln([
            '',
            '<comment>Done!</comment>',
            '',
        ]);

        return self::RETURN_CODE_OK;
    }

    private function getCharacterEncodingFromOldInvalidCharacterEncodingOutput(Output $taskOutput): string
    {
        $content = json_decode($taskOutput->getOutput(), true);
        $messages = $content['messages'];
        $message = $messages[0];
        $messageText = $message['message'];

        $codeFragmentMatches = [];
        preg_match('/<code>[^<]+<\/code>/', $messageText, $codeFragmentMatches);

        return strip_tags($codeFragmentMatches[0]);
    }
}
