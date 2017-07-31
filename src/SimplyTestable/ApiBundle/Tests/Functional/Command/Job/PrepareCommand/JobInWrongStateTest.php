<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Job\PrepareCommand;

use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class JobInWrongStateTest extends CommandTest
{
    protected function preCall()
    {
        $this->queuePrepareHttpFixturesForJob($this->job->getWebsite()->getCanonicalUrl());
    }

    protected function getJob()
    {
        $jobFactory = new JobFactory($this->container);

        return $jobFactory->create();
    }

    protected function getExpectedReturnCode()
    {
        return 1;
    }
}
