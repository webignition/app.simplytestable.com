<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\StatusAction\IssueCount\WarningCount;

class ZeroPerTaskTest extends WarningCountTest {    
    
    protected function getReportedWarningCount() {
        return 0;
    }

}