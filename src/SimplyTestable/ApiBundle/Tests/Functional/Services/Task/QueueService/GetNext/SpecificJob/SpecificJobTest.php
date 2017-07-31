<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Task\QueueService\GetNext\SpecificJob;

use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\Task\QueueService\ServiceTest;
use SimplyTestable\ApiBundle\Entity\Job\Job;

class SpecificJobTest extends ServiceTest
{
    /**
     * @var Job[]
     */
    private $jobs;

    /**
     * @var int[]
     */
    private $nextTaskIds = [];

    public function setUp()
    {
        parent::setUp();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $jobFactory = new JobFactory($this->container);

        $this->jobs[] = $jobFactory->createResolveAndPrepare([
            JobFactory::KEY_SITE_ROOT_URL => 'http://foo.example.com/',
        ], [
            'prepare' => [
                HttpFixtureFactory::createStandardRobotsTxtResponse(),
                HttpFixtureFactory::createStandardSitemapResponse('foo.example.com/'),
            ],
        ]);

        $this->jobs[] = $jobFactory->createResolveAndPrepare([
            JobFactory::KEY_SITE_ROOT_URL => 'http://bar.example.com/',
        ], [
            'prepare' => [
                HttpFixtureFactory::createStandardRobotsTxtResponse(),
                HttpFixtureFactory::createStandardSitemapResponse('bar.example.com/'),
            ],
        ]);

        $this->getTaskQueueService()->setLimit($this->jobs[1]->getTasks()->count());
        $this->getTaskQueueService()->setJob($this->jobs[1]);

        $this->nextTaskIds = $this->getTaskQueueService()->getNext();
    }

    public function testNextTaskIdsBelongToSpecifiedJob()
    {
        foreach ($this->jobs[1]->getTasks() as $task) {
            $this->assertTrue(in_array($task->getId(), $this->nextTaskIds));
        }
    }
}
