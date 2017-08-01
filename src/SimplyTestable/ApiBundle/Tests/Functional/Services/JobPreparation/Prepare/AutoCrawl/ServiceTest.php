<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\JobPreparation\Prepare\AutoCrawl;

use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

/**
 * Test cases were a crawl job should and should not be created
 */
class ServiceTest extends BaseSimplyTestableTestCase
{
    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobFactory = new JobFactory($this->container);

        $this->queueHttpFixtures(array_merge(
            [
                HttpFixtureFactory::createStandardResolveResponse(),
            ],
            HttpFixtureFactory::createStandardCrawlPrepareResponses()
        ));

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
    }

    public function testPublicUserJobDoesNotAutostartCrawlJob()
    {
        $user = $this->getUserService()->getPublicUser();
        $this->getUserService()->setUser($user);

        $job = $this->jobFactory->create();
        $this->jobFactory->resolve($job);
        $this->getJobPreparationService()->prepare($job);

        $this->assertFalse($this->getCrawlJobContainerService()->hasForJob($job));
    }

    public function testNonPublicUserJobDoesAutostartCrawlJob()
    {
        $userFactory = new UserFactory($this->container);
        $user = $userFactory->createAndActivateUser();

        $job = $this->jobFactory->create([
            JobFactory::KEY_USER => $user,
        ]);
        $this->jobFactory->resolve($job);

        $this->getJobPreparationService()->prepare($job);

        $this->assertTrue($this->getCrawlJobContainerService()->hasForJob($job));
    }
}
