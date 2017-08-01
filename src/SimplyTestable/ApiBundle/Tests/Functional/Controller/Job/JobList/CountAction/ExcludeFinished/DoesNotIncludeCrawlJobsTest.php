<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\CountAction\ExcludeFinished;

use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class DoesNotIncludeCrawlJobsTest extends StateBasedTest
{
    private $canonicalUrls = array(
        'http://crawling.example.com/',
    );

    protected function getRequestingUser()
    {
        $userFactory = new UserFactory($this->container);

        return $userFactory->create();
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
        $jobFactory = new JobFactory($this->container);
        $userFactory = new UserFactory($this->container);

        // Crawling job
        $this->jobs[] = $jobFactory->createResolveAndPrepare([
            JobFactory::KEY_SITE_ROOT_URL => $this->canonicalUrls[0],
            JobFactory::KEY_USER => $userFactory->create(),
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
