<?php

namespace SimplyTestable\ApiBundle\Tests\Command\ScheduledJob\ExecuteCommand;

use SimplyTestable\ApiBundle\Command\ScheduledJob\ExecuteCommand;

class UnroutableWebsiteTest extends RejectedTest {

    protected function getExpectedReturnCode() {
        return ExecuteCommand::RETURN_CODE_UNROUTABLE;
    }

    protected function getJobConfigurationWebsite() {
        return 'http://foo';
    }

    protected function getExpectedRejectionReason()
    {
        return 'unroutable';
    }
}
