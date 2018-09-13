<?php

namespace App\Command\Migrate;

use App\Entity\Job\Job;
use App\Repository\JobRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Task\Task;
use App\Services\ApplicationStateService;
use App\Services\TaskTypeService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveJsStaticAnalysisTasksCommand extends Command
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
     * @var TaskTypeService
     */
    private $taskTypeService;

    /**
     * @param ApplicationStateService $applicationStateService
     * @param EntityManagerInterface $entityManager
     * @param TaskTypeService $taskTypeService
     * @param string|null $name
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        EntityManagerInterface $entityManager,
        TaskTypeService $taskTypeService,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->entityManager = $entityManager;
        $this->taskTypeService = $taskTypeService;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:migrate:remove-js-static-analysis-tasks')
            ->setDescription(
                'Remove all JS static analysis tasks'
            )
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

        $output->write('<info>Finding all jobs with JS static analysis tasks ... </info>');

        /* @var JobRepository $jobRepository */
        $jobRepository = $this->entityManager->getRepository(Job::class);
        $jsTaskType = $this->taskTypeService->getJsStaticAnalysisTaskType();

        /* @var Job[] $jobs */
        $jobs = $jobRepository->getByTaskType($jsTaskType);
        $jobCount = count($jobs);

        $output->writeln([
            sprintf('<comment>%s</comment> found', $jobCount),
            '',
        ]);

        $processingDuration = 0;
        $processedJobCount = 0;
        $batchProcessedDuration = 0;
        $batchProcessedJobCount = 0;

        foreach ($jobs as $jobIndex => $job) {
            $timestampBefore = microtime(true);

            $jobNumber = $jobIndex + 1;

            $output->writeln(sprintf(
                '<info>Processing job</info> %s of %s <comment>%s</comment>',
                $jobNumber,
                $jobCount,
                $job->getId()
            ));

            /* @var Task[] $tasks */
            $tasks = $job->getTasks();
            $taskCount = count($tasks);
            $jsTasks = [];

            foreach ($tasks as $task) {
                if (TaskTypeService::JS_STATIC_ANALYSIS_TYPE === $task->getType()->getName()) {
                    $jsTasks[] = $task;
                }
            }

            $jsTaskCount = count($jsTasks);

            $output->writeln(sprintf(
                '<comment>%s</comment> <info>total tasks</info>; <comment>%s</comment> <info>JS tasks</info>',
                $taskCount,
                $jsTaskCount
            ));

            $output->writeln('    Removing JS tasks ...');

            foreach ($jsTasks as $jsTask) {
                $job->removeTask($jsTask);
                $this->entityManager->remove($jsTask);
            }

            $output->writeln('    Removing JS JobTaskType ...');

            $job->removeRequestedTaskType($jsTaskType);
            $this->entityManager->persist($job);

            if (!$isDryRun) {
                $this->entityManager->flush();
            }

            $timestampAfter = microtime(true);
            $duration = $timestampAfter - $timestampBefore;

            $output->writeln(sprintf(
                '<info>Job duration</info>: <comment>%s</comment> seconds',
                number_format($duration, 2)
            ));

            $processingDuration += $duration;
            $processedJobCount++;

            $output->writeln(sprintf(
                '<info>Total duration</info>: <comment>%s</comment> minutes',
                number_format($processingDuration / 60, 2)
            ));

            $remainingJobCount = $jobCount - $processedJobCount;

            $batchProcessedDuration += $duration;
            $batchProcessedJobCount++;

            $estimatedRemainingDurationByBatch =
                ($batchProcessedDuration / $batchProcessedJobCount) * $remainingJobCount;

            if ($batchProcessedJobCount % 5 === 0) {
                $output->writeln(sprintf(
                    '<info>Estimated remaining duration</info>: <comment>%s</comment> minutes',
                    number_format($estimatedRemainingDurationByBatch / 60, 2)
                ));
            }

            if ($batchProcessedJobCount >=10) {
                $batchProcessedJobCount = 0;
                $batchProcessedDuration = 0;
            }

            $output->writeln('');
        }

        $output->writeln([
            '',
            '<info>Done!</info>',
            '',
        ]);

        return self::RETURN_CODE_OK;
    }
}
