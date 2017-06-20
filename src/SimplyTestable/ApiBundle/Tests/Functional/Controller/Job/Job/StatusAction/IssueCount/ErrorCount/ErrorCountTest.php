<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\StatusAction\IssueCount\ErrorCount;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\StatusAction\IssueCount\IssueCountTest;

abstract class ErrorCountTest extends IssueCountTest {

    protected function getReportedWarningCount() {
        return 0;
    }

}