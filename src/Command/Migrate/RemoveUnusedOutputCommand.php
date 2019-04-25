<?php

namespace App\Command\Migrate;

use App\Command\AbstractLockableCommand;
use App\Command\DryRunOptionTrait;
use App\Repository\TaskOutputRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Services\ApplicationStateService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\Factory as LockFactory;

class RemoveUnusedOutputCommand extends AbstractLockableCommand
{
    use DryRunOptionTrait;

    const DEFAULT_FLUSH_THRESHOLD = 100;

    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;
    const RETURN_CODE_UNABLE_TO_ACQUIRE_LOCK = 2;

    private $applicationStateService;
    private $entityManager;
    private $taskOutputRepository;

    public function __construct(
        LockFactory $lockFactory,
        ApplicationStateService $applicationStateService,
        EntityManagerInterface $entityManager,
        TaskOutputRepository $taskOutputRepository,
        $name = null
    ) {
        parent::__construct($lockFactory, $name);

        $this->applicationStateService = $applicationStateService;
        $this->entityManager = $entityManager;
        $this->taskOutputRepository = $taskOutputRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:migrate:remove-unused-output')
            ->setDescription('Remove output not linked to any task')
            ->addOption('limit')
            ->addOption('flush-threshold');

        $this->addDryRunOption();
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

        $isDryRun = $this->isDryRun($input);

        if ($isDryRun) {
            $this->outputIsDryRunNotification($output);
        }

        if (!$isDryRun) {
            if (!$this->createAndAcquireLock()) {
                $output->writeln('Unable to acquire lock, ending');

                return self::RETURN_CODE_UNABLE_TO_ACQUIRE_LOCK;
            }
        }

        $output->writeln('Finding unused output ...');

        $unusedTaskOutputIds = $this->taskOutputRepository->findUnusedIds($this->getLimit($input));

        if (empty($unusedTaskOutputIds)) {
            $output->writeln('No unused task outputs found. Done.');

            return self::RETURN_CODE_OK;
        }

        $output->writeln('['.count($unusedTaskOutputIds).'] outputs found');
        $processedTaskOutputCount = 0;

        $flushThreshold = $this->getFlushTreshold($input);
        $persistCount = 0;

        foreach ($unusedTaskOutputIds as $unusedTaskOutputId) {
            $taskOutputToRemove = $this->taskOutputRepository->find($unusedTaskOutputId);

            $processedTaskOutputCount++;
            $output->writeln(sprintf(
                'Removing output [%s] (%s remaining)',
                $unusedTaskOutputId,
                (count($unusedTaskOutputIds) - $processedTaskOutputCount)
            ));

            if (!$isDryRun) {
                $this->entityManager->remove($taskOutputToRemove);
            }

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

        $this->releaseLock();

        return self::RETURN_CODE_OK;
    }

    /**
     * @param InputInterface $input
     *
     * @return int
     */
    private function getLimit(InputInterface $input)
    {
        if ($input->getOption('limit') === false) {
            return 0;
        }

        $limit = filter_var($input->getOption('limit'), FILTER_VALIDATE_INT);

        return ($limit <= 0) ? 0 : $limit;
    }

    /**
     * @param InputInterface $input
     *
     * @return int
     */
    private function getFlushTreshold($input)
    {
        return $this->getIntegerOptionWithDefault(
            $input,
            'flush-threshold',
            self::DEFAULT_FLUSH_THRESHOLD
        );
    }

    /**
     * @param InputInterface $input
     * @param string $optionName
     * @param mixed $defaultValue
     *
     * @return int
     */
    private function getIntegerOptionWithDefault($input, $optionName, $defaultValue)
    {
        $value = $input->getOption($optionName);
        if ($value <= 0) {
            return $defaultValue;
        }

        return (int)$value;
    }
}
