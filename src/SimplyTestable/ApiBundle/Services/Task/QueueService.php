<?php
namespace SimplyTestable\ApiBundle\Services\Task;

use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\TaskService;

class QueueService {

    /**
     *
     * @var JobService
     */
    protected $jobService;


    /**
     *
     * @var TaskService
     */
    protected $taskService;


    /**
     * @param JobService $jobService
     * @param TaskService $taskService
     */
    public function __construct(JobService $jobService, TaskService $taskService) {
        $this->jobService = $jobService;
        $this->taskService = $taskService;
    }

    /**
     * @param int $limit
     * @return int[]
     */
    public function getNext($limit = 1) {
        $incompleteJobs = $this->jobService->getEntityRepository()->getByStatesAndTaskStates(
            $this->jobService->getIncompleteStates(),
            [
                $this->taskService->getQueuedState(),
                $this->taskService->getQueuedForAssignmentState(),
            ]
        );

        if (count($incompleteJobs) === 0) {
            return [];
        }

        $jobTaskIds = [];
        foreach ($incompleteJobs as $job) {
            $taskIdsForJob = $this->taskService->getEntityRepository()->getIdsByJobAndTaskStates($job, [
                $this->taskService->getQueuedState(),
                $this->taskService->getQueuedForAssignmentState()
            ], $limit);

            if (count($taskIdsForJob)) {
                $jobTaskIds[$job->getId()] =  $taskIdsForJob;
            }
        }


        $taskIds = [];
        while (count($taskIds) < ($limit) && count($jobTaskIds) > 0) {
            foreach ($jobTaskIds as $jobId => $taskIdSet) {
                $taskIds[] = array_shift($taskIdSet);

                if (count($taskIdSet) === 0) {
                    unset($jobTaskIds[$jobId]);
                } else {
                    $jobTaskIds[$jobId] = $taskIdSet;
                }
            }
        }

        if (count($taskIds) > $limit) {
            $taskIds = array_slice($taskIds, 0, $limit);
        }

        return $taskIds;
    }

}