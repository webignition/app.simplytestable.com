<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\StatusAction\IssueCount\WarningCount;

class ZeroPerTaskTest extends WarningCountTest {

    protected function getReportedWarningCount() {
        return 0;
    }

}