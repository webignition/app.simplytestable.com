<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\CancelAction;

use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class NewJobTest extends IsCancelledTest
{
    protected function getJob()
    {
        $jobFactory = new JobFactory($this->container);

        return $jobFactory->create();
    }

    protected function getExpectedJobStartingState()
    {
        return $this->getJobService()->getStartingState();
    }
}
