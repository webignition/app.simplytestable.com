<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job\PrepareCommand\SuccessfulPrepare;

use SimplyTestable\ApiBundle\Tests\Command\Job\PrepareCommand\CommandTest;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

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
        $job = $jobFactory->create([
            JobFactory::KEY_TEST_TYPES => ['js static analysis'],
            JobFactory::KEY_TEST_TYPE_OPTIONS => [
                'js static analysis' => [
                    'ignore-common-cdns' => 1
                ],
            ],
            JobFactory::KEY_PARAMETERS => [
                'http-auth-username' => 'user',
                'http-auth-password' => 'pass'
            ],
        ]);
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

    public function testCommonCdnsToIgnoreAreSetOnDomainsToIgnore()
    {
        $task = $this->job->getTasks()->first();

        $this->assertEquals(
            json_decode($task->getParameters(), true)['domains-to-ignore'],
            $this->container->getParameter('js-static-analysis-domains-to-ignore')
        );
    }

    public function testJobParametersAreSetOnTask()
    {
        $task = $this->job->getTasks()->first();

        $this->assertEquals(
            json_decode($task->getParameters(), true)['http-auth-username'],
            'user'
        );

        $this->assertEquals(
            json_decode($task->getParameters(), true)['http-auth-password'],
            'pass'
        );
    }

    private function getWorkerCount()
    {
        $classNameParts = explode('\\', get_class($this));
        return (int)str_replace(['Worker', 'Test'], '', $classNameParts[count($classNameParts) - 1]);
    }
}
