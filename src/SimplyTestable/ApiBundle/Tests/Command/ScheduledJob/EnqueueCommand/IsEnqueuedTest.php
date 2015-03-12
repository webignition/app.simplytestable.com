<?php

namespace SimplyTestable\ApiBundle\Tests\Command\ScheduledJob\EnqueueCommand;

class IsEnqueuedTest extends CommandTest {

    protected function getExpectedReturnCode() {
        return 0;
    }

    public function testResqueExecuteJobIsEnqueued() {
        $this->assertTrue($this->getResqueQueueService()->contains('scheduledjob-execute', [
            'id' => 1
        ]));
    }
}
