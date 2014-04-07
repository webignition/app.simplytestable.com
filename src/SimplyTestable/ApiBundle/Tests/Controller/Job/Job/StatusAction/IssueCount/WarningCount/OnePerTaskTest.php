<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\StatusAction\IssueCount\WarningCount;

class OnePerTaskTest extends WarningCountTest {
        
    protected function getReportedWarningCount() {
        return 1;
    }

}