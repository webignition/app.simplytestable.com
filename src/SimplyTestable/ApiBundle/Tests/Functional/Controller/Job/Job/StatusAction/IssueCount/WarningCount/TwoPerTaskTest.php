<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\StatusAction\IssueCount\WarningCount;

class TwoPerTaskTest extends WarningCountTest {

    protected function getReportedWarningCount() {
        return 2;
    }

}