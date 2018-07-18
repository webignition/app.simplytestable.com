<?php

namespace AppBundle\Command\Migrate;

use Doctrine\ORM\EntityManagerInterface;
use AppBundle\Entity\Task\Output;
use AppBundle\Entity\Task\Task;
use AppBundle\Services\ApplicationStateService;
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
        if ($this->applicationStateService->isInReadOnlyMode()) {
            $output->writeln('In maintenance-read-only mode, I can\'t do that right now');

            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $isDryRun = $input->getOption('dry-run');

        $output->writeln('Finding duplicate output ...');

        $taskRepository = $this->entityManager->getRepository(Task::class);
        $taskOutputRepository = $this->entityManager->getRepository(Output::class);

        $duplicateHashes = $taskOutputRepository->findDuplicateHashes($this->getLimit($input));

        if (empty($duplicateHashes)) {
            $output->writeln('No duplicate output found. Done.');

            return self::RETURN_CODE_OK;
        }

        $output->writeln('Processing ' . count($duplicateHashes) . ' duplicate hashes');
        $globalUpdatedTaskCount = 0;
        $updatedHashCount = 0;

        foreach ($duplicateHashes as $duplicateHash) {
            $outputIds = $taskOutputRepository->findIdsByHash($duplicateHash);

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
                $sourceOutput = $taskOutputRepository->find($sourceId);
                $duplicatesToRemove = array_slice($outputIds, 1);
                $updatedTaskCount = 0;

                foreach ($duplicatesToRemove as $taskOutputId) {
                    $processedDuplicateHashCount++;

                    $taskOutput = $taskOutputRepository->find($taskOutputId);

                    $tasksToUpdate = $taskRepository->findBy([
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
                                $this->entityManager->flush();
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
