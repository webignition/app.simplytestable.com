<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\StatusAction\IssueCount\WarningCount;

class OnePerTaskTest extends WarningCountTest {

    protected function getReportedWarningCount() {
        return 1;
    }

}