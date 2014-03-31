<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\StatusAction\IssueCount\ErrorCount;

class ThreePerTaskTest extends ErrorCountTest {    
    
    protected function getReportedErrorCount() {
        return 3;
    }

}