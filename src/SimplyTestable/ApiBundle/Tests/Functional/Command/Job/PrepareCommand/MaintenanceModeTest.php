<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Job\PrepareCommand;

use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class MaintenanceModeTest extends CommandTest
{
    protected function preCall()
    {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
    }

    protected function getJob()
    {
        $jobFactory = new JobFactory($this->container);

        $job = $jobFactory->create();
        $jobFactory->resolve($job);

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        return $job;
    }

    protected function getExpectedReturnCode()
    {
        return 2;
    }

    public function testResqueJobIsRequeued()
    {
        $this->assertTrue($this->getResqueQueueService()->contains('job-prepare', [
            'id' => $this->job->getId()
        ]));
    }
}
