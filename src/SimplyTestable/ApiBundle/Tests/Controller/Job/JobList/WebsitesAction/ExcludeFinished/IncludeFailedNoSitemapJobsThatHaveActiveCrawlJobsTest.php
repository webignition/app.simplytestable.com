<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\WebsitesAction\ExcludeFinished;

use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class IncludeFailedNoSitemapJobsThatHaveActiveCrawlJobsTest extends StateBasedTest
{
    protected function getExpectedWebsitesList()
    {
        return $this->getCanonicalUrls();
    }

    protected function getCanonicalUrls()
    {
        return array(self::DEFAULT_CANONICAL_URL);
    }

    protected function getRequestingUser()
    {
        return $this->getTestUser();
    }

    protected function createJobs()
    {
        $this->jobs[] = $this->createJobFactory()->createResolveAndPrepare([
            JobFactory::KEY_USER => $this->getTestUser(),
        ], [
            'prepare' => HttpFixtureFactory::createStandardCrawlPrepareResponses(),
        ]);
    }
}
