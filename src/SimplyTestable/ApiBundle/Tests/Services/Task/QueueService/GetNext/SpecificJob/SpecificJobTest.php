<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\QueueService\GetNext\SpecificJob;

use SimplyTestable\ApiBundle\Tests\Services\Task\QueueService\ServiceTest;
use SimplyTestable\ApiBundle\Entity\Job\Job;

class SpecificJobTest extends ServiceTest {

    /**
     * @var Job[]
     */
    private $jobs;


    /**
     * @var int[]
     */
    private $nextTaskIds = [];

    public function setUp() {
        parent::setUp();

        $this->jobs[] = $this->getJobService()->getById($this->createResolveAndPrepareJob('http://foo.example.com/'));
        $this->jobs[] = $this->getJobService()->getById($this->createResolveAndPrepareJob('http://bar.example.com/'));

        $this->getTaskQueueService()->setLimit($this->jobs[1]->getTasks()->count());
        $this->getTaskQueueService()->setJob($this->jobs[1]);

        $this->nextTaskIds = $this->getTaskQueueService()->getNext();
    }


    public function testNextTaskIdsBelongToSpecifiedJob() {
        foreach ($this->jobs[1]->getTasks() as $task) {
            $this->assertTrue(in_array($task->getId(), $this->nextTaskIds));
        }
    }

}
