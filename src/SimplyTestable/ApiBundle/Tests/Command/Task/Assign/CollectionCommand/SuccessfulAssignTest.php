<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Task\Assign\CollectionCommand;

use SimplyTestable\ApiBundle\Entity\Job\Job;

class SuccessfulAssignTest extends CollectionCommandTest {

    /**
     * @var int[]
     */
    private $taskIds = [];


    /**
     * @var Job
     */
    private $job;


    private $executeReturnCode = null;

    public function setUp() {
        parent::setUp();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $this->job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(null). '/HttpResponses')));
        $this->createWorker();

        $this->taskIds = $this->getTaskIds($this->job);

        $this->executeReturnCode = $this->execute(['ids' => implode($this->taskIds, ',')]);
    }


    public function testExecuteReturnCodeIs0() {
        $this->assertEquals(0, $this->executeReturnCode);
    }


    public function testTasksHaveWorkers() {
        foreach ($this->job->getTasks() as $task) {
            $this->assertNotNull($task->getWorker());
        }
    }


    public function testTasksAreInProgress() {
        foreach ($this->job->getTasks() as $task) {
            $this->assertEquals($this->getTaskService()->getInProgressState(), $task->getState());
        }
    }

}