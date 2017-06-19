<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\ExcludeCurrent;

use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class CrawlJobTest extends ExcludeCurrentTest
{
    protected function getCanonicalUrls()
    {
        return array(self::DEFAULT_CANONICAL_URL);
    }

    protected function getExpectedJobListUrls()
    {
        return array();
    }

    protected function getExpectedListLength()
    {
        return 0;
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
