<?php

namespace SimplyTestable\ApiBundle\Command\Migrate;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Task\Output;
use SimplyTestable\ApiBundle\Repository\TaskOutputRepository;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveUnusedOutputCommand extends Command
{
    const DEFAULT_FLUSH_THRESHOLD = 100;

    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;

    /**
     * @var ApplicationStateService
     */
    private $applicationStateService;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var TaskOutputRepository
     */
    private $taskOutputRepository;

    /**
     * @param ApplicationStateService $applicationStateService
     * @param EntityManager $entityManager
     * @param string|null $name
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        EntityManager $entityManager,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->entityManager = $entityManager;
        $this->taskOutputRepository = $entityManager->getRepository(Output::class);
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
            ->addOption('flush-threshold')
            ->addOption('dry-run');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->applicationStateService->isInMaintenanceReadOnlyState()) {
            $output->writeln('In maintenance-read-only mode, I can\'t do that right now');
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $isDryRun = $input->getOption('dry-run');

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
