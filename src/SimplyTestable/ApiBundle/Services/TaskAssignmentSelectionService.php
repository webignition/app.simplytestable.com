<?php
namespace SimplyTestable\ApiBundle\Services;

use Psr\Log\LoggerInterface;
use SimplyTestable\ApiBundle\Entity\Job\Job;

class TaskAssignmentSelectionService
{
    /**
     * @var JobService
     */
    private $jobService;

    /**
     * @var TaskService
     */
    private $taskService;
    /**
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param JobService $jobService
     * @param TaskService $taskService
     * @param LoggerInterface $logger
     */
    public function __construct(
        JobService $jobService,
        TaskService $taskService,
        LoggerInterface $logger
    ) {
        $this->jobService = $jobService;
        $this->taskService = $taskService;
        $this->logger = $logger;
    }

    /**
     * Get a limited collection of queued tasks from each job that has queued tasks
     *
     * @param int $workerCount
     *
     * @return array
     */
    public function selectTasks($workerCount = 0)
    {
        if ($workerCount === 0) {
            return array();
        }

        $this->logger->info('TaskAssignmentSelectionService:selectTasks:start');

        $jobs = $this->jobService->getJobsWithQueuedTasks();

        $taskInProgressState = $this->taskService->getInProgressState();
        $taskQueuedForAssignmentState = $this->taskService->getQueuedForAssignmentState();

        $tasks = array();
        foreach ($jobs as $jobIndex => $job) {
            /* @var $job Job */
            $this->logger->info(sprintf(
                'TaskAssignmentSelectionService:selectTasks [job%s] [%s] [%s]',
                $jobIndex,
                $job->getId(),
                $job->getWebsite()
            ));

            $inProgressTaskCount = $this->taskService->getCountByJobAndState($job, $taskInProgressState);
            $queuedForAssignmentTaskCount = $this->taskService->getCountByJobAndState(
                $job,
                $taskQueuedForAssignmentState
            );

            $limitForThisJob = ($workerCount * 2)  - $inProgressTaskCount - $queuedForAssignmentTaskCount;

            $this->logger->info(sprintf(
                'TaskAssignmentSelectionService:selectTasks:inProgressTaskCount: [job%s] [%s]',
                $jobIndex,
                $inProgressTaskCount
            ));

            $this->logger->info(sprintf(
                'TaskAssignmentSelectionService:selectTasks:queuedForAssignmentTaskCount: [job%s] [%s]',
                $jobIndex,
                $queuedForAssignmentTaskCount
            ));

            $this->logger->info(sprintf(
                'TaskAssignmentSelectionService:selectTasks:limitForThisJob: [job%s] [%s]',
                $jobIndex,
                $limitForThisJob
            ));

            if ($limitForThisJob > 0) {
                $tasks = array_merge($tasks, $this->jobService->getQueuedTasks($job, $limitForThisJob));
            }
        }

        return $tasks;
    }
}
