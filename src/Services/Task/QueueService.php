<?php

namespace App\Services\Task;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\State;
use App\Entity\Task\Task;
use App\Repository\JobRepository;
use App\Repository\TaskRepository;
use App\Services\JobService;
use App\Services\StateService;
use App\Services\TaskService;

class QueueService
{
    private $taskService;
    private $jobRepository;

    /**
     * @var State[]
     */
    private $incompleteJobStates;

    /**
     * @var State
     */
    private $taskQueuedState;

    /**
     * @var int
     */
    private $limit = 1;

    /**
     * @var TaskRepository
     */
    private $taskRepository;

    public function __construct(
        JobService $jobService,
        TaskService $taskService,
        StateService $stateService,
        EntityManagerInterface $entityManager,
        JobRepository $jobRepository
    ) {
        $this->taskService = $taskService;

        $this->jobRepository = $jobRepository;
        $this->taskRepository = $entityManager->getRepository(Task::class);

        $this->incompleteJobStates = $stateService->getCollection($jobService->getIncompleteStateNames());
        $this->taskQueuedState = $stateService->get(Task::STATE_QUEUED);
    }

    /**
     * @param int $limit
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * @return int[]
     */
    public function getNext()
    {
        $incompleteJobsWithQueuedTasks = $this->jobRepository->getByStatesAndTaskStates(
            $this->incompleteJobStates,
            [
                $this->taskQueuedState
            ]
        );

        if (empty($incompleteJobsWithQueuedTasks)) {
            return [];
        }

        $jobTaskIds = [];
        foreach ($incompleteJobsWithQueuedTasks as $job) {
            $taskIdsForJob = $this->taskRepository->getIdsByJobAndStates(
                $job,
                [$this->taskQueuedState],
                $this->limit
            );

            if (count($taskIdsForJob)) {
                $jobTaskIds[$job->getId()] =  $taskIdsForJob;
            }
        }

        $taskIds = [];
        while (count($taskIds) < ($this->limit) && count($jobTaskIds) > 0) {
            foreach ($jobTaskIds as $jobId => $taskIdSet) {
                $taskIds[] = array_shift($taskIdSet);

                if (count($taskIdSet) === 0) {
                    unset($jobTaskIds[$jobId]);
                } else {
                    $jobTaskIds[$jobId] = $taskIdSet;
                }
            }
        }

        if (count($taskIds) > $this->limit) {
            $taskIds = array_slice($taskIds, 0, $this->limit);
        }

        return $taskIds;
    }
}
