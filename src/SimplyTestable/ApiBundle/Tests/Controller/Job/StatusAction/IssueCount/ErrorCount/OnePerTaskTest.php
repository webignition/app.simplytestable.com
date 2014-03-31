<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\StatusAction\IssueCount\ErrorCount;

class OnePerTaskTest extends ErrorCountTest {
        
    protected function getReportedErrorCount() {
        return 1;
    }

}