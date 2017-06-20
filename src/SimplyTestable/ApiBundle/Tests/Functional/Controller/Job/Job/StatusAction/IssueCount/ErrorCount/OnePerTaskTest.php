<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\StatusAction\IssueCount\ErrorCount;

class OnePerTaskTest extends ErrorCountTest {

    protected function getReportedErrorCount() {
        return 1;
    }

}