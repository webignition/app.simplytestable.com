<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\StatusAction\IssueCount\ErrorCount;

class OnePerTaskTest extends ErrorCountTest {
        
    protected function getReportedErrorCount() {
        return 1;
    }

}