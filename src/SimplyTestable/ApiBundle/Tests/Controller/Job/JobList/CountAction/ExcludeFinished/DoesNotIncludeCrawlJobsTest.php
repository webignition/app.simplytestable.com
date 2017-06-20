<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\CountAction\ExcludeFinished;

use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class DoesNotIncludeCrawlJobsTest extends StateBasedTest
{
    private $canonicalUrls = array(
        'http://crawling.example.com/',
    );

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
        return $this->canonicalUrls;
    }

    protected function createJobs()
    {
        // Crawling job
        $this->jobs[] = $this->createJobFactory()->createResolveAndPrepare([
            JobFactory::KEY_SITE_ROOT_URL => $this->canonicalUrls[0],
            JobFactory::KEY_USER => $this->getTestUser(),
        ], [
            'prepare' => HttpFixtureFactory::createStandardCrawlPrepareResponses(),
        ]);
    }

    protected function getPostParameters()
    {
        return array(
            'user' => $this->jobs[0]->getUser()->getEmail()
        );
    }
}
