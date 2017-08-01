<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\ListAction\ExcludeCurrent;

use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

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
        $jobFactory = new JobFactory($this->container);
        $userFactory = new UserFactory($this->container);

        $this->jobs[] = $jobFactory->createResolveAndPrepare([
            JobFactory::KEY_USER => $userFactory->create(),
        ], [
            'prepare' => HttpFixtureFactory::createStandardCrawlPrepareResponses(),
        ]);
    }
}
