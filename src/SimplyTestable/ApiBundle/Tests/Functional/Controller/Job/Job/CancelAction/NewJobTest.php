<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\CancelAction;

class NewJobTest extends IsCancelledTest
{
    protected function getJob()
    {
        return $this->createJobFactory()->create();
    }

    protected function getExpectedJobStartingState()
    {
        return $this->getJobService()->getStartingState();
    }
}
