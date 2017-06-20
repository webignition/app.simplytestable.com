<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Start\StartAction;

class MaintenanceModeReadOnlyTest extends SingleResponseTest {

    public function preCall() {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
    }

    /**
     * @return int
     */
    protected function getExpectedResponseStatusCode()
    {
        return 503;
    }
}