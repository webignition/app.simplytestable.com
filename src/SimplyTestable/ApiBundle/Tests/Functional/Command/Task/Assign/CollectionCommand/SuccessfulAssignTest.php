<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Task\Assign\CollectionCommand;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class SuccessfulAssignTest extends CollectionCommandTest
{
    /**
     * @var int[]
     */
    private $taskIds = [];

    /**
     * @var Job
     */
    private $job;

    /**
     * @var int
     */
    private $executeReturnCode = null;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $jobFactory = new JobFactory($this->container);

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $this->job = $jobFactory->createResolveAndPrepare();
        $this->queueHttpFixtures(
            $this->buildHttpFixtureSet(
                $this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(null). '/HttpResponses')
            )
        );
        $this->createWorker();

        $this->taskIds = $this->getTaskIds($this->job);

        $this->executeReturnCode = $this->execute(['ids' => implode($this->taskIds, ',')]);
    }

    public function testExecuteReturnCodeIs0()
    {
        $this->assertEquals(0, $this->executeReturnCode);
    }

    public function testTasksHaveWorkers()
    {
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
