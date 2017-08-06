<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\TaskPreProcessor\LinkIntegrity;

use SimplyTestable\ApiBundle\Tests\Factory\WorkerFactory;

class ViaAssignCollectionCommandTest extends PreProcessorTest
{
    protected function setUp()
    {
        parent::setUp();

        /* @var $task \SimplyTestable\ApiBundle\Entity\Task\Task */
        $task = $this->tasks->get(1);

        $workerFactory = new WorkerFactory($this->container);
        $workerFactory->create();

        $this->executeCommand('simplytestable:task:assigncollection', array(
            'ids' => $task->getId()
        ));
    }

    protected function getCompletedTaskOutput()
    {
        return $this->getDefaultCompletedTaskOutput();
    }

    public function testDetermineCorrectErrorCount()
    {
        $this->assertEquals(0, $this->tasks->get(1)->getOutput()->getErrorCount());
    }
}
