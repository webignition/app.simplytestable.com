<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Job\PrepareCommand\NoUrlsDiscovered;

use SimplyTestable\ApiBundle\Tests\Functional\Command\Job\PrepareCommand\CommandTest;

class PublicUserTest extends CommandTest
{
    protected function preCall()
    {
        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 404',
            'HTTP/1.0 404',
            'HTTP/1.0 404',
            'HTTP/1.0 404',
        )));
    }

    protected function getJob()
    {
        $user = $this->getUserService()->getPublicUser();
        $this->getUserService()->setUser($user);

        $jobFactory = $this->createJobFactory();
        $job = $jobFactory->create();
        $jobFactory->resolve($job);

        return $job;
    }

    protected function getExpectedReturnCode()
    {
        return 0;
    }

    public function testResqueJobIsNotCreated()
    {
        $this->assertTrue($this->getResqueQueueService()->isEmpty('task-assign-collection'));
    }
}