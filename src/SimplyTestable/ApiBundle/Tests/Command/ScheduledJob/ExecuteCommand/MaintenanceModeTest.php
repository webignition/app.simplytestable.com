<?php

namespace SimplyTestable\ApiBundle\Tests\Command\ScheduledJob\ExecuteCommand;

use SimplyTestable\ApiBundle\Command\ScheduledJob\ExecuteCommand;

class MaintenanceModeTest extends WithoutScheduledJobTest {

    protected function preCall() {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
    }

    protected function getExpectedReturnCode() {
        return ExecuteCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
    }


    protected function getScheduledJobId()
    {
        return 1;
    }


    public function testResqueExecuteJobIsEnqueued() {
        $this->assertTrue($this->getResqueQueueService()->contains('scheduledjob-execute', [
            'id' => $this->getScheduledJobId()
        ]));
    }
}
