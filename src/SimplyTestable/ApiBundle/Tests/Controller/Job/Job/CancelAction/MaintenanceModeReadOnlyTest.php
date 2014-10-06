<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\CancelAction;

class MaintenanceModeReadOnlyTest extends CancelTest {

    protected function preCall() {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
    }


    protected function getJob() {
        return $this->getJobService()->getById($this->createJobAndGetId(self::DEFAULT_CANONICAL_URL));
    }

    protected function getExpectedJobStartingState() {
        return $this->getJobService()->getStartingState();
    }

    protected function getExpectedJobEndingState() {
        return $this->getExpectedJobStartingState();
    }

    protected function getExpectedResponseCode() {
        return 503;
    }
    
}


