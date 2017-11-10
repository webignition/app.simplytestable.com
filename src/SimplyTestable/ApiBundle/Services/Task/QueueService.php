<?php
namespace SimplyTestable\ApiBundle\Services\Task;

use Doctrine\ORM\EntityManagerInterface;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Repository\JobRepository;
use SimplyTestable\ApiBundle\Repository\TaskRepository;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\TaskService;

class QueueService
{
    /**
     * @var TaskService
     */
    private $taskService;

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
     * @var JobRepository
     */
    private $jobRepository;

    /**
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * @param JobService $jobService
     * @param TaskService $taskService
     * @param StateService $stateService
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        JobService $jobService,
        TaskService $taskService,
        StateService $stateService,
        EntityManagerInterface $entityManager
    ) {
        $this->taskService = $taskService;

        $this->jobRepository = $entityManager->getRepository(Job::class);
        $this->taskRepository = $entityManager->getRepository(Task::class);

        $this->incompleteJobStates = $stateService->getCollection($jobService->getIncompleteStateNames());
        $this->taskQueuedState = $stateService->get(TaskService::QUEUED_STATE);
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
