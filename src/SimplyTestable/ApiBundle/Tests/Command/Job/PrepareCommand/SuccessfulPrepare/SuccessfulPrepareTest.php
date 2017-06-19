<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job\PrepareCommand\SuccessfulPrepare;

use SimplyTestable\ApiBundle\Tests\Command\Job\PrepareCommand\CommandTest;

abstract class SuccessfulPrepareTest extends CommandTest
{
    protected function preCall()
    {
        $this->queuePrepareHttpFixturesForJob($this->job->getWebsite()->getCanonicalUrl());

        $fixture = 'HTTP/1.1 200 OK';
        $fixtures = [];

        for ($count = 0; $count < $this->getWorkerCount(); $count++) {
            $fixtures[] = $fixture;
        }

        $this->queueHttpFixtures($this->buildHttpFixtureSet($fixtures));
        $this->createWorkers($this->getWorkerCount());
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

    public function testResqueTasksNotifyJobIsCreated()
    {
        $this->assertFalse($this->getResqueQueueService()->isEmpty(
            'tasks-notify'
        ));
    }

    private function getWorkerCount()
    {
        $classNameParts = explode('\\', get_class($this));
        return (int)str_replace(['Worker', 'Test'], '', $classNameParts[count($classNameParts) - 1]);
    }
}
