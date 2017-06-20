<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\CountAction\ExcludeFinished;

use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class IncludeFailedNoSitemapJobsThatHaveActiveCrawlJobsTest extends StateBasedTest
{
    protected function getRequestingUser()
    {
        return $this->getTestUser();
    }

    protected function getExpectedCountValue()
    {
        return count($this->getCanonicalUrls());
    }

    protected function getCanonicalUrls()
    {
        return array(self::DEFAULT_CANONICAL_URL);
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
