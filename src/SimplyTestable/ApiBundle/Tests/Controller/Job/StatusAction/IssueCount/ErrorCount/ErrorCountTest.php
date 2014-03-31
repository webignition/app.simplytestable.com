<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\StatusAction\IssueCount\ErrorCount;

use SimplyTestable\ApiBundle\Tests\Controller\Job\StatusAction\IssueCount\IssueCountTest;

abstract class ErrorCountTest extends IssueCountTest {

    protected function getReportedWarningCount() {
        return 0;
    }

}