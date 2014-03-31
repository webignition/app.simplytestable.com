<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\StatusAction\IssueCount\ErrorCount;

class ZeroPerTaskTest extends ErrorCountTest {    
    
    protected function getReportedErrorCount() {
        return 0;
    }

}