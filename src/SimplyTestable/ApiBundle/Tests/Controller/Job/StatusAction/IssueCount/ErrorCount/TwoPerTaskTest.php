<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\StatusAction\IssueCount\ErrorCount;

class TwoPerTaskTest extends ErrorCountTest {    
    
    protected function getReportedErrorCount() {
        return 2;
    }

}