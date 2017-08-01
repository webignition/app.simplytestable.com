<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Job\PrepareCommand\NoUrlsDiscovered;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Command\Job\PrepareCommand\CommandTest;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class PrivateUserTest extends CommandTest
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
        $jobFactory = new JobFactory($this->container);
        $userFactory = new UserFactory($this->container);
        $user = $userFactory->createAndActivateUser();

        $this->getUserService()->setUser($user);

        $job = $jobFactory->create([
            JobFactory::KEY_USER => $user,
        ]);
        $jobFactory->resolve($job);

        return $job;
    }

    protected function getExpectedReturnCode()
    {
        return 0;
    }

    public function testTaskAssignCollectionResqueJobForCrawlJobFirstTaskIsCreated()
    {
        $crawlJob = $this->getCrawlJobContainerService()->getForJob($this->job)->getCrawlJob();

        $this->assertTrue($this->getResqueQueueService()->contains(
            'task-assign-collection',
            ['ids' => $crawlJob->getTasks()->first()->getId()]
        ));
    }
}
