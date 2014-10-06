<?php
namespace SimplyTestable\ApiBundle\Services\Task;

use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Entity\Job\Job;

class QueueService {

    /**
     *
     * @var JobService
     */
    private $jobService;


    /**
     *
     * @var TaskService
     */
    private $taskService;


    /**
     * @var int
     */
    private $limit = 1;


    /**
     * @var Job
     */
    private $job = null;


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
     * @return $this
     */
    public function setLimit($limit) {
        $this->limit = $limit;
        return $this;
    }


    /**
     * @return int
     */
    public function getLimit() {
        return $this->limit;
    }


    /**
     * @param Job $job
     * @return $this
     */
    public function setJob(Job $job) {
        $this->job = $job;
        return $this;
    }


    /**
     * @return Job
     */
    public function getJob() {
        return $this->job;
    }


    /**
     * @return $this
     */
    public function clearJob() {
        $this->job = null;
        return $this;
    }



    /**
     * @return int[]
     */
    public function getNext() {
        $incompleteJobs = $this->getIncompleteJobSet();
        if (count($incompleteJobs) === 0) {
            return [];
        }

        $jobTaskIds = [];
        foreach ($incompleteJobs as $job) {
            $taskIdsForJob = $this->taskService->getEntityRepository()->getIdsByJobAndTaskStates($job, [
                $this->taskService->getQueuedState(),
                $this->taskService->getQueuedForAssignmentState()
            ], $this->getLimit());

            if (count($taskIdsForJob)) {
                $jobTaskIds[$job->getId()] =  $taskIdsForJob;
            }
        }

        $taskIds = [];
        while (count($taskIds) < ($this->getLimit()) && count($jobTaskIds) > 0) {
            foreach ($jobTaskIds as $jobId => $taskIdSet) {
                $taskIds[] = array_shift($taskIdSet);

                if (count($taskIdSet) === 0) {
                    unset($jobTaskIds[$jobId]);
                } else {
                    $jobTaskIds[$jobId] = $taskIdSet;
                }
            }
        }

        if (count($taskIds) > $this->getLimit()) {
            $taskIds = array_slice($taskIds, 0, $this->getLimit());
        }

        return $taskIds;
    }


    private function getIncompleteJobSet() {
        $incompleteJobs =  $this->jobService->getEntityRepository()->getByStatesAndTaskStates(
            $this->jobService->getIncompleteStates(),
            [
                $this->taskService->getQueuedState(),
                $this->taskService->getQueuedForAssignmentState(),
            ]
        );

        if (!is_null($this->job)) {
            foreach ($incompleteJobs as $jobIndex => $job) {
                if ($job->getId() !== $this->job->getId()) {
                    unset($incompleteJobs[$jobIndex]);
                }
            }
        }

        return $incompleteJobs;
    }

}