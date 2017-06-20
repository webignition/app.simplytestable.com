<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\StatusAction\IssueCount\ErrorCount;

class TwoPerTaskTest extends ErrorCountTest {

    protected function getReportedErrorCount() {
        return 2;
    }

}