<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Task\Assign\CollectionCommand;

use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class NoWorkersAvailableTest extends CollectionCommandTest
{
    /**
     * @var int[]
     */
    private $taskIds = [];

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

        $job = $jobFactory->createResolveAndPrepare();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $this->taskIds = $this->getTaskIds($job);

        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 404',
            'HTTP/1.0 404',
            'HTTP/1.0 404',
        )));

        $this->createWorker('hydrogen.worker.simplytestable.com');
        $this->createWorker('lithium.worker.simplytestable.com');
        $this->createWorker('helium.worker.simplytestable.com');

        $this->executeReturnCode = $this->execute(['ids' => implode($this->taskIds, ',')]);
    }

    public function testExecuteReturnCodeIs2()
    {
        $this->assertEquals(2, $this->executeReturnCode);
    }

    public function testResqueJobIsCreated()
    {
        $this->assertTrue($this->getResqueQueueService()->contains(
            'task-assign-collection',
            array(
                'ids' => implode(',', $this->taskIds)
            )
        ));
    }
}
