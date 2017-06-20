<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\TaskPreProcessor\LinkIntegrity;

class ViaAssignSelectedCommandTest extends PreProcessorTest {

    public function setUp() {
        parent::setUp();

        $task = $this->tasks->get(1);

        $task->setState($this->getTaskService()->getQueuedForAssignmentState());
        $this->getTaskService()->getManager()->persist($task);
        $this->getTaskService()->getManager()->flush();

        $this->createWorker();

        $this->executeCommand('simplytestable:task:assign-selected');
    }

    protected function getCompletedTaskOutput() {
        return $this->getDefaultCompletedTaskOutput();
    }

    public function testDetermineCorrectErrorCount() {
        $this->assertEquals(0, $this->tasks->get(1)->getOutput()->getErrorCount());
    }


}
