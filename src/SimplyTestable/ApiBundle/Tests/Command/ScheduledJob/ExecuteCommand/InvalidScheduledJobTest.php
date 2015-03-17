<?php

namespace SimplyTestable\ApiBundle\Tests\Command\ScheduledJob\ExecuteCommand;

use SimplyTestable\ApiBundle\Command\ScheduledJob\ExecuteCommand;

class InvalidScheduledJobTest extends WithoutScheduledJobTest {

    protected function getExpectedReturnCode() {
        return ExecuteCommand::RETURN_CODE_INVALID_SCHEDULED_JOB;
    }


    protected function getScheduledJobId()
    {
        return 1;
    }

}
