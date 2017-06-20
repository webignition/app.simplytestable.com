<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\StatusAction\IssueCount\WarningCount;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\StatusAction\IssueCount\IssueCountTest;

abstract class WarningCountTest extends IssueCountTest {

    protected function getReportedErrorCount() {
        return 0;
    }

}