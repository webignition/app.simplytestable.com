<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\StatusAction\IssueCount\WarningCount;

class ThreePerTaskTest extends WarningCountTest {    
    
    protected function getReportedWarningCount() {
        return 3;
    }

}