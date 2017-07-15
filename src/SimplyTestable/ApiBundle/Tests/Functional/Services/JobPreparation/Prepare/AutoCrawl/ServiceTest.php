<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\JobPreparation\Prepare\AutoCrawl;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

/**
 * Test cases were a crawl job should and should not be created
 */
class ServiceTest extends BaseSimplyTestableTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->queueResolveHttpFixture();
        $this->queuePrepareHttpFixturesForCrawlJob(self::DEFAULT_CANONICAL_URL);

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
    }

    public function testPublicUserJobDoesNotAutostartCrawlJob()
    {
        $user = $this->getUserService()->getPublicUser();
        $this->getUserService()->setUser($user);

        $jobFactory = $this->createJobFactory();
        $job = $jobFactory->create();
        $jobFactory->resolve($job);
        $this->getJobPreparationService()->prepare($job);

        $this->assertFalse($this->getCrawlJobContainerService()->hasForJob($job));
    }

    public function testNonPublicUserJobDoesAutostartCrawlJob()
    {
        $user = $this->createAndActivateUser('user@example.com', 'password');

        $jobFactory = $this->createJobFactory();
        $job = $jobFactory->create([
            JobFactory::KEY_USER => $user,
        ]);
        $jobFactory->resolve($job);

        $this->getJobPreparationService()->prepare($job);

        $this->assertTrue($this->getCrawlJobContainerService()->hasForJob($job));
    }
}