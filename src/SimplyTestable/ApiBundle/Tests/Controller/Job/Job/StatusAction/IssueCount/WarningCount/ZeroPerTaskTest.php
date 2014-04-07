<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\StatusAction\IssueCount\WarningCount;

class ZeroPerTaskTest extends WarningCountTest {    
    
    protected function getReportedWarningCount() {
        return 0;
    }

}