<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\TaskPreProcessor\LinkIntegrity;

class ViaAssignCollectionCommandTest extends PreProcessorTest {

    protected function setUp() {
        parent::setUp();

        /* @var $task \SimplyTestable\ApiBundle\Entity\Task\Task */
        $task = $this->tasks->get(1);

        $this->createWorker();
        $this->executeCommand('simplytestable:task:assigncollection', array(
            'ids' => $task->getId()
        ));
    }

    protected function getCompletedTaskOutput() {
        return $this->getDefaultCompletedTaskOutput();
    }

    public function testDetermineCorrectErrorCount() {
        $this->assertEquals(0, $this->tasks->get(1)->getOutput()->getErrorCount());
    }


}
