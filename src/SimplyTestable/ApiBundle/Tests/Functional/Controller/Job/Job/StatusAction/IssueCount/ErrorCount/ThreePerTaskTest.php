<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\StatusAction\IssueCount\ErrorCount;

class ThreePerTaskTest extends ErrorCountTest {

    protected function getReportedErrorCount() {
        return 3;
    }

}