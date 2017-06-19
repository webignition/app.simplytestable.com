<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class ServiceTest extends BaseSimplyTestableTestCase
{
    const EXPECTED_TASK_TYPE_COUNT = 4;

    public function testHandleSitemapContainingSchemelessUrls()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->getJobService()->getById($this->createAndResolveDefaultJob());

        $this->queueHttpFixtures(
            $this->buildHttpFixtureSet(
                $this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath($this->getName()). '/HttpResponses')
            )
        );
        $this->getJobPreparationService()->prepare($job);

        $this->assertTrue($job->getTasks()->isEmpty());
        $this->assertEquals($this->getJobService()->getFailedNoSitemapState(), $job->getState());
    }

    public function testHandleSingleIndexLargeSitemap()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->getJobService()->getById($this->createAndResolveDefaultJob());

        $this
            ->getWebSiteService()
            ->getSitemapFinder()
            ->getSitemapRetriever()
            ->getConfiguration()
            ->setTotalTransferTimeout(0.00001);

        $this->queueHttpFixtures(
            $this->buildHttpFixtureSet(
                $this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath($this->getName()). '/HttpResponses')
            )
        );
        $this->getJobPreparationService()->prepare($job);

        $expectedUrlCount =
            $this
                ->getUserAccountPlanService()
                ->getForUser($this->getUserService()->getPublicUser())
                ->getPlan()
                ->getConstraintNamed('urls_per_job')
                ->getLimit();

        $this->assertEquals(self::EXPECTED_TASK_TYPE_COUNT * $expectedUrlCount, $job->getTasks()->count());
    }

    public function testHandleLargeCollectionOfSitemaps()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->getJobService()->getById($this->createAndResolveDefaultJob());

        $this->queueHttpFixtures(
            $this->buildHttpFixtureSet(
                $this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath($this->getName()). '/HttpResponses')
            )
        );
        $this->getJobPreparationService()->prepare($job);

        $expectedUrlCount =
            $this
                ->getUserAccountPlanService()
                ->getForUser($this->getUserService()->getPublicUser())
                ->getPlan()
                ->getConstraintNamed('urls_per_job')
                ->getLimit();

        $this->assertEquals(self::EXPECTED_TASK_TYPE_COUNT * $expectedUrlCount, $job->getTasks()->count());
    }

    public function testHandleMalformedRssUrl()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->getJobService()->getById($this->createAndResolveDefaultJob());

        $this->queueHttpFixtures(
            $this->buildHttpFixtureSet(
                $this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath($this->getName()). '/HttpResponses')
            )
        );
        $this->getJobPreparationService()->prepare($job);

        $this->assertTrue($job->getTasks()->isEmpty());
        $this->assertEquals($this->getJobService()->getFailedNoSitemapState(), $job->getState());
    }

    public function testCrawlJobTakesParametersOfParentJob()
    {
        $user = $this->getTestUser();
        $this->getUserService()->setUser($user);

        $jobFactory = $this->createJobFactory();
        $job = $jobFactory->create([
            JobFactory::KEY_USER => $user,
            JobFactory::KEY_PARAMETERS => [
                'http-auth-username' => 'example',
                'http-auth-password' => 'password'
            ],
        ]);
        $jobFactory->resolve($job);

        $this->queuePrepareHttpFixturesForCrawlJob($job->getWebsite()->getCanonicalUrl());
        $this->getJobPreparationService()->prepare($job);

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->assertEquals(
            $crawlJobContainer->getParentJob()->getParameters(),
            $crawlJobContainer->getCrawlJob()->getParameters()
        );
    }
}
