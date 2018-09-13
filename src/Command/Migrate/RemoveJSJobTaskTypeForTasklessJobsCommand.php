<?php

namespace App\Command\Migrate;

use App\Entity\Job\Job;
use App\Repository\JobRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Services\ApplicationStateService;
use App\Services\TaskTypeService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveJSJobTaskTypeForTasklessJobsCommand extends Command
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
            ->setName('simplytestable:migrate:remove-js-job-task-type-for-taskless-jobs')
            ->setDescription(
                'Remove JS-related JobTaskType entity for jobs with no tasks'
            )
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

        /* @var JobRepository $jobRepository */
        $jobRepository = $this->entityManager->getRepository(Job::class);
        $jsTaskType = $this->taskTypeService->getJsStaticAnalysisTaskType();

        $output->write('<info>Finding jobs when JS JobTaskType and no tasks</info> ... ');

        $jobIds = $jobRepository->getIdsForJobsWithJsJobTaskTypeAndNoTasks($jsTaskType);
        $jobCount = count($jobIds);

        $output->writeln([
            '<comment>' . $jobCount . '</comment> jobs found',
            '',
        ]);

        foreach ($jobIds as $jobIndex => $jobId) {
            $jobNumber = $jobIndex + 1;

            $output->writeln(sprintf(
                '<info>Processing job</info> %s of %s <comment>%s</comment>',
                $jobNumber,
                $jobCount,
                $jobId
            ));

            /* @var Job $job */
            $job = $jobRepository->find($jobId);

            $job->removeRequestedTaskType($jsTaskType);

            $this->entityManager->persist($job);

            if (!$isDryRun) {
                $this->entityManager->flush();
            }
        }

        $output->writeln([
            '',
            '<info>Done!</info>',
            '',
        ]);

        return self::RETURN_CODE_OK;
    }
}
