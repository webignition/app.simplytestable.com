<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Task\Assign\CollectionCommand;

use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class NoWorkersTest extends CollectionCommandTest
{
    /**
     * @var int[]
     */
    private $taskIds = [];

    /**
     * @var int
     */
    private $executeReturnCode = null;

    public function setUp()
    {
        parent::setUp();

        $jobFactory = new JobFactory($this->container);

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $jobFactory->createResolveAndPrepare();
        $this->taskIds = $this->getTaskIds($job);

        $this->executeReturnCode = $this->execute(['ids' => implode($this->taskIds, ',')]);
    }

    public function testExecuteReturnCodeIs1()
    {
        $this->assertEquals(1, $this->executeReturnCode);
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
