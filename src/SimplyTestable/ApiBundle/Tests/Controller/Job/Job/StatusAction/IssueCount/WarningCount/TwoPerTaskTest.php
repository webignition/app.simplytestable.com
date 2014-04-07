<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\StatusAction\IssueCount\WarningCount;

class TwoPerTaskTest extends WarningCountTest {    
    
    protected function getReportedWarningCount() {
        return 2;
    }

}