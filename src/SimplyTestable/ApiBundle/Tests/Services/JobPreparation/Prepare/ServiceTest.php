<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class ServiceTest extends BaseSimplyTestableTestCase
{
    const EXPECTED_TASK_TYPE_COUNT = 4;

    public function testHandleSitemapContainingSchemelessUrls()
    {
        $user = $this->getUserService()->getPublicUser();
        $this->getUserService()->setUser($user);

        $jobFactory = $this->createJobFactory();
        $job = $jobFactory->create();
        $jobFactory->resolve($job);

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
        $user = $this->getUserService()->getPublicUser();
        $this->getUserService()->setUser($user);

        $jobFactory = $this->createJobFactory();
        $job = $jobFactory->create([
            JobFactory::KEY_TEST_TYPES => [
                'html validation',
                'css validation',
                'js static analysis',
                'link integrity',
            ],
        ]);
        $jobFactory->resolve($job);

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
        $user = $this->getUserService()->getPublicUser();
        $this->getUserService()->setUser($user);

        $jobFactory = $this->createJobFactory();
        $job = $jobFactory->create([
            JobFactory::KEY_TEST_TYPES => [
                'html validation',
                'css validation',
                'js static analysis',
                'link integrity',
            ],
        ]);
        $jobFactory->resolve($job);

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
        $user = $this->getUserService()->getPublicUser();
        $this->getUserService()->setUser($user);

        $jobFactory = $this->createJobFactory();
        $job = $jobFactory->create();
        $jobFactory->resolve($job);

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
