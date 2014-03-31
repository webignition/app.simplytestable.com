<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\StatusAction\IssueCount\WarningCount;

class TwoPerTaskTest extends WarningCountTest {    
    
    protected function getReportedWarningCount() {
        return 2;
    }

}