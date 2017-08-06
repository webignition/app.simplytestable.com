<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\TaskPreProcessor\LinkIntegrity;

use SimplyTestable\ApiBundle\Tests\Factory\WorkerFactory;

class ViaAssignSelectedCommandTest extends PreProcessorTest
{
    protected function setUp()
    {
        parent::setUp();

        $task = $this->tasks->get(1);

        $task->setState($this->getTaskService()->getQueuedForAssignmentState());
        $this->getTaskService()->getManager()->persist($task);
        $this->getTaskService()->getManager()->flush();

        $workerFactory = new WorkerFactory($this->container);
        $workerFactory->create();

        $this->executeCommand('simplytestable:task:assign-selected');
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
