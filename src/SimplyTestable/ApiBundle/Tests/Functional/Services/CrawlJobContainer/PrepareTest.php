<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\CrawlJobContainer;

use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class PrepareTest extends BaseSimplyTestableTestCase
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
    }

    public function testForInProgressState()
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $jobInProgressState = $stateService->fetch(JobService::IN_PROGRESS_STATE);

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $job = $this->jobFactory->createResolveAndPrepare([], [
            'prepare' => HttpFixtureFactory::createStandardCrawlPrepareResponses(),
        ]);

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $crawlJobContainer->getCrawlJob()->setState($jobInProgressState);

        $this->assertFalse($this->getCrawlJobContainerService()->prepare($crawlJobContainer));
    }

    public function testForCompletedState()
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $jobCompletedState = $stateService->fetch(JobService::COMPLETED_STATE);

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $job = $this->jobFactory->createResolveAndPrepare([], [
            'prepare' => HttpFixtureFactory::createStandardCrawlPrepareResponses(),
        ]);

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $crawlJobContainer->getCrawlJob()->setState($jobCompletedState);

        $this->assertFalse($this->getCrawlJobContainerService()->prepare($crawlJobContainer));
    }

    public function testForQueuedState()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $job = $this->jobFactory->createResolveAndPrepare([], [
            'prepare' => HttpFixtureFactory::createStandardCrawlPrepareResponses(),
        ]);

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);

        $this->assertTrue($this->getCrawlJobContainerService()->prepare($crawlJobContainer));
        $this->assertEquals(1, $crawlJobContainer->getCrawlJob()->getTasks()->count());
        $this->assertEquals(
            'URL discovery',
            $crawlJobContainer->getCrawlJob()->getTasks()->first()->getType()->getName()
        );
        $this->assertEquals('job-queued', $crawlJobContainer->getCrawlJob()->getState()->getName());
        $this->assertNotNull($crawlJobContainer->getCrawlJob()->getTimePeriod());
    }

    public function testPrepareIsIdempotent()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $job = $this->jobFactory->createResolveAndPrepare([], [
            'prepare' => HttpFixtureFactory::createStandardCrawlPrepareResponses(),
        ]);

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);

        $this->assertTrue($this->getCrawlJobContainerService()->prepare($crawlJobContainer));
        $this->assertTrue($this->getCrawlJobContainerService()->prepare($crawlJobContainer));
        $this->assertEquals(1, $crawlJobContainer->getCrawlJob()->getTasks()->count());
        $this->assertEquals(
            'URL discovery',
            $crawlJobContainer->getCrawlJob()->getTasks()->first()->getType()->getName()
        );
    }

    public function testUrlDiscoveryTaskHasWwwAndNonWwwScope()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $job = $this->jobFactory->createResolveAndPrepare([], [
            'prepare' => HttpFixtureFactory::createStandardCrawlPrepareResponses(),
        ]);

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);

        $this->assertTrue($this->getCrawlJobContainerService()->prepare($crawlJobContainer));
        $this->assertEquals(
            '{"scope":["http:\/\/example.com\/","http:\/\/www.example.com\/"]}',
            $crawlJobContainer->getCrawlJob()->getTasks()->first()->getParameters()
        );
    }

    public function testUrlDiscoveryTaskHasNonWwwAndWwwScope()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $job = $this->jobFactory->createResolveAndPrepare([], [
            'prepare' => HttpFixtureFactory::createStandardCrawlPrepareResponses(),
        ]);

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);

        $this->assertTrue($this->getCrawlJobContainerService()->prepare($crawlJobContainer));
        $this->assertEquals(
            '{"scope":["http:\/\/example.com\/","http:\/\/www.example.com\/"]}',
            $crawlJobContainer->getCrawlJob()->getTasks()->first()->getParameters()
        );
    }
}
