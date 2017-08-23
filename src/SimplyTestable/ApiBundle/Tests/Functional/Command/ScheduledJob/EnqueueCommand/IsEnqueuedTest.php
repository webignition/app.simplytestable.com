<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\ScheduledJob\EnqueueCommand;

class IsEnqueuedTest extends CommandTest {

    protected function getExpectedReturnCode() {
        return 0;
    }

    public function testResqueExecuteJobIsEnqueued() {
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');

        $this->assertTrue($resqueQueueService->contains('scheduledjob-execute', [
            'id' => 1
        ]));
    }
}
