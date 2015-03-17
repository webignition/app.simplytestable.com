<?php

namespace SimplyTestable\ApiBundle\Tests\Command\ScheduledJob\ExecuteCommand;

use SimplyTestable\ApiBundle\Command\ScheduledJob\ExecuteCommand;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\User;

class SuccessTest extends WithScheduledJobTest {

    protected function getExpectedReturnCode() {
        return ExecuteCommand::RETURN_CODE_OK;
    }
}
