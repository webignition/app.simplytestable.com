<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\CancelAction;

class MaintenanceModeReadOnlyTest extends CancelTest
{
    protected function preCall()
    {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
    }

    protected function getJob()
    {
        return $this->createJobFactory()->create();
    }

    protected function getExpectedJobStartingState()
    {
        return $this->getJobService()->getStartingState();
    }

    protected function getExpectedJobEndingState()
    {
        return $this->getExpectedJobStartingState();
    }

    protected function getExpectedResponseCode()
    {
        return 503;
    }
}
