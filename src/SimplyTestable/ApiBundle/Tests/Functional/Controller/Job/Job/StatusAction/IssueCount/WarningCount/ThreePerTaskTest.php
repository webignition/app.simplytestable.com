<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\StatusAction\IssueCount\WarningCount;

class ThreePerTaskTest extends WarningCountTest {

    protected function getReportedWarningCount() {
        return 3;
    }

}