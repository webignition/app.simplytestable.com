<?php

namespace SimplyTestable\ApiBundle\Tests\Command\ScheduledJob\EnqueueCommand;

class IsNotReenqueuedTest extends CommandTest {

    protected function preCall() {
        $this->executeCommand('simplytestable:scheduledjob:enqueue', [1]);
    }

    protected function getExpectedReturnCode() {
        return 0;
    }

    public function testResqueExecuteJobIsEnqueued() {
        $this->assertTrue($this->getResqueQueueService()->contains('scheduledjob-execute', [
            'id' => 1
        ]));
    }
}
