<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\StatusAction\IssueCount\ErrorCount;

class ZeroPerTaskTest extends ErrorCountTest {    
    
    protected function getReportedErrorCount() {
        return 0;
    }

}