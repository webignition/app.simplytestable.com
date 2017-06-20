<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\StatusAction\IssueCount\ErrorCount;

class ZeroPerTaskTest extends ErrorCountTest {

    protected function getReportedErrorCount() {
        return 0;
    }

}