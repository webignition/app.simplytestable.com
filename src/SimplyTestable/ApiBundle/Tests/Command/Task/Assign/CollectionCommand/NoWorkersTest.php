<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Task\Assign\CollectionCommand;

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

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->createJobFactory()->createResolveAndPrepare();
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
