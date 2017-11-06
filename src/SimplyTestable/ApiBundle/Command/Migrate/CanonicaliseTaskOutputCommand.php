<?php

namespace SimplyTestable\ApiBundle\Command\Migrate;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Task\Output;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Repository\TaskOutputRepository;
use SimplyTestable\ApiBundle\Repository\TaskRepository;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CanonicaliseTaskOutputCommand extends Command
{
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
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * @var TaskOutputRepository
     */
    private $taskOutputRepository;

    /**
     * @param ApplicationStateService $applicationStateService
     * @param EntityManager $entityManager
     * @param TaskRepository $taskRepository
     * @param TaskOutputRepository $taskOutputRepository
     * @param string|null $name
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        EntityManager $entityManager,
        TaskRepository $taskRepository,
        TaskOutputRepository $taskOutputRepository,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->entityManager = $entityManager;
        $this->taskRepository = $taskRepository;
        $this->taskOutputRepository = $taskOutputRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:migrate:canonicalise-task-output')
            ->setDescription('Update tasks to point to canonical output')
            ->addOption('limit')
            ->addOption('dry-run')
        ;
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

        $output->writeln('Finding duplicate output ...');

        $duplicateHashes = $this->taskOutputRepository->findDuplicateHashes($this->getLimit($input));

        if (empty($duplicateHashes)) {
            $output->writeln('No duplicate output found. Done.');

            return self::RETURN_CODE_OK;
        }

        $output->writeln('Processing ' . count($duplicateHashes) . ' duplicate hashes');
        $globalUpdatedTaskCount = 0;
        $updatedHashCount = 0;

        foreach ($duplicateHashes as $duplicateHash) {
            $outputIds = $this->taskOutputRepository->findIdsByHash($duplicateHash);

            $updatedHashCount++;
            $output->writeln(sprintf(
                '[%s] duplicates found for %s (%s remaining)',
                (count($outputIds) - 1),
                $duplicateHash,
                (count($duplicateHashes) - $updatedHashCount)
            ));

            $duplicateHashCount = count($outputIds) - 1;
            $processedDuplicateHashCount = 0;

            if (count($outputIds) > 1) {
                $sourceId = $outputIds[0];
                $sourceOutput = $this->taskOutputRepository->find($sourceId);
                $duplicatesToRemove = array_slice($outputIds, 1);
                $updatedTaskCount = 0;

                foreach ($duplicatesToRemove as $taskOutputId) {
                    $processedDuplicateHashCount++;

                    $taskOutput = $this->taskOutputRepository->find($taskOutputId);

                    $tasksToUpdate = $this->taskRepository->findBy([
                        'output' => $taskOutput,
                    ]);

                    $duplicateHashTaskCount = count($tasksToUpdate);
                    $processedDuplicateHashTaskCount = 0;

                    if (!empty($tasksToUpdate)) {
                        foreach ($tasksToUpdate as $task) {
                            /* @var Task $task */
                            $updatedTaskCount++;
                            $processedDuplicateHashTaskCount++;

                            $output->writeln(sprintf(
                                'Updating output for task [%s] (%s batches remaining, %s tasks remaining in batch)',
                                $task->getId(),
                                ($duplicateHashCount - $processedDuplicateHashCount),
                                ($duplicateHashTaskCount - $processedDuplicateHashTaskCount)
                            ));

                            if (!$isDryRun) {
                                $task->setOutput($sourceOutput);
                                $this->entityManager->persist($task);
                                $this->entityManager->flush($task);
                            }
                        }
                    }
                }

                if ($updatedTaskCount === 0) {
                    $output->writeln('No tasks using duplicates of ' . $duplicateHash);
                }

                $globalUpdatedTaskCount += $updatedTaskCount;

                $output->writeln('');
            }
        }

        $output->writeln('['.$globalUpdatedTaskCount.'] tasks updated');

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
}
