<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\StatusAction\IssueCount\WarningCount;

use SimplyTestable\ApiBundle\Tests\Controller\Job\StatusAction\IssueCount\IssueCountTest;

abstract class WarningCountTest extends IssueCountTest {

    protected function getReportedErrorCount() {
        return 0;
    }

}