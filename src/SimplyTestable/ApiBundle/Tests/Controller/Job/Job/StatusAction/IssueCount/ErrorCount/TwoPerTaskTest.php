<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\StatusAction\IssueCount\ErrorCount;

class TwoPerTaskTest extends ErrorCountTest {    
    
    protected function getReportedErrorCount() {
        return 2;
    }

}