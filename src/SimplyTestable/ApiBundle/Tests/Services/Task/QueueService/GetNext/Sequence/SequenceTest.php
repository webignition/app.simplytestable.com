<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\QueueService\GetNext\Sequence;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Tests\Services\Task\QueueService\ServiceTest;

abstract class SequenceTest extends ServiceTest {

    /**
     * @var Job[]
     */
    private $jobs;


    /**
     * @var int[]
     */
    private $firstSetTaskIds = [];

    public function setUp() {
        parent::setUp();

        for ($jobLimitIndex = 0; $jobLimitIndex < $this->getJobLimit(); $jobLimitIndex++) {
            $this->jobs[] = $this->getJobService()->getById(
                $this->createResolveAndPrepareJob('http://' . $jobLimitIndex . '.example.com/')
            );
        }

        $this->getService()->setLimit($this->getTaskLimit());

        $this->firstSetTaskIds = $this->getService()->getNext();

        foreach ($this->firstSetTaskIds as $taskId) {
            $task = $this->getTaskService()->getById($taskId);
            $task->setState($this->getTaskService()->getCompletedState());
            $this->getTaskService()->persistAndFlush($task);
        }
    }


    /**
     * @return int
     */
    private function getJobLimit() {
        $classNameParts = explode('\\', get_class($this));
        $localClassName = array_pop($classNameParts);

        $matches = [];
        preg_match('/Job[0-9]+/', $localClassName, $matches);

        return (int)str_replace('Job', '', $matches[0]);
    }


    /**
     * @return int
     */
    private function getTaskLimit() {
        $classNameParts = explode('\\', get_class($this));
        $localClassName = array_pop($classNameParts);

        $matches = [];
        preg_match('/Task[0-9]+/', $localClassName, $matches);

        return (int)str_replace('Task', '', $matches[0]);
    }

    private function getExpectedTaskIds() {
        $allJobTaskIds = [];
        foreach ($this->jobs as $job) {
            $allJobTaskIds[$job->getId()] =  $this->getTaskService()->getEntityRepository()->getIdsByJob($job);
        }

        $jobTaskIds = [];
        foreach ($allJobTaskIds as $jobId => $taskIdSet) {
            $jobTaskIds[$jobId] = [];

            foreach ($taskIdSet as $taskId) {
                if (!in_array($taskId, $this->firstSetTaskIds)) {
                    $jobTaskIds[$jobId][] = $taskId;
                }
            }
        }

        $maxJobTaskIds = 0;
        foreach ($allJobTaskIds as $taskIdSet) {
            if (count($taskIdSet) > $maxJobTaskIds) {
                $maxJobTaskIds = count($taskIdSet);
            }
        }

        $taskIds = [];
        for ($taskIdIndex = 0; $taskIdIndex < $maxJobTaskIds; $taskIdIndex++) {
            foreach ($jobTaskIds as $taskIdSet) {
                if (isset($taskIdSet[$taskIdIndex])) {
                    $taskIds[] = $taskIdSet[$taskIdIndex];
                }
            }
        }

        if (count($taskIds) > $this->getTaskLimit()) {
            $taskIds = array_slice($taskIds, 0, $this->getTaskLimit());
        }

        return $taskIds;
    }

    public function testGetsExpectedTaskIds() {
        $this->assertEquals($this->getExpectedTaskIds(), $this->getService()->getNext());
    }

}
