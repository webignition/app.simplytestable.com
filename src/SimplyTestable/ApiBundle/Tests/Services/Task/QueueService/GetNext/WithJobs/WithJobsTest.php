<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\QueueService\GetNext\WithJobs;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Tests\Services\Task\QueueService\ServiceTest;

abstract class WithJobsTest extends ServiceTest {

    /**
     * @var Job[]
     */
    private $jobs;

    public function setUp() {
        parent::setUp();

        for ($jobLimitIndex = 0; $jobLimitIndex < $this->getJobLimit(); $jobLimitIndex++) {
            $this->jobs[] = $this->getJobService()->getById(
                $this->createResolveAndPrepareJob('http://' . $jobLimitIndex . '.example.com/')
            );
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
        $jobTaskIds = [];
        foreach ($this->jobs as $job) {
            $jobTaskIds[$job->getId()] =  $this->getTaskService()->getEntityRepository()->getIdsByJob($job, $this->getTaskLimit());
        }

        $taskIds = [];
        while (count($taskIds) < ($this->getTaskLimit()) && count($jobTaskIds) > 0) {
            foreach ($jobTaskIds as $jobId => $taskIdSet) {
                $taskIds[] = array_shift($taskIdSet);

                if (count($taskIdSet) === 0) {
                    unset($jobTaskIds[$jobId]);
                } else {
                    $jobTaskIds[$jobId] = $taskIdSet;
                }
            }
        }

        if (count($taskIds) > $this->getTaskLimit()) {
            $taskIds = array_slice($taskIds, 0, $this->getTaskLimit());
        }

        return $taskIds;
    }

    public function testGetsExpectedTaskIds() {
        $this->assertEquals($this->getExpectedTaskIds(), $this->getService()->getNext($this->getTaskLimit()));
    }

}