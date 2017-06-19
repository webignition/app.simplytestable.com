<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\ExcludeFinished;

use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class ListIsSortedByJobIdTest extends StateBasedTest
{
    /**
     * @var string[]
     */
    private $canonicalUrls = array(
        'http://non-crawling.example.com/',
        'http://crawling.example.com/',
    );

    protected function getRequestingUser()
    {
        return $this->getTestUser();
    }

    protected function getExpectedListLength()
    {
        return count($this->getCanonicalUrls());
    }

    protected function getCanonicalUrls()
    {
        return $this->canonicalUrls;
    }

    protected function getExpectedJobListUrls()
    {
        return array_reverse($this->getCanonicalUrls());
    }

    protected function createJobs()
    {
        $jobFactory = $this->createJobFactory();

        // Non-crawling job
        $this->jobs[] = $jobFactory->createResolveAndPrepare([
            JobFactory::KEY_SITE_ROOT_URL => $this->canonicalUrls[0],
            JobFactory::KEY_USER => $this->getTestUser(),
        ], [
            'prepare' => [
                HttpFixtureFactory::createStandardRobotsTxtResponse(),
                HttpFixtureFactory::createStandardSitemapResponse($this->canonicalUrls[0]),
            ],
        ]);

        // Crawling job
        $this->jobs[] = $jobFactory->createResolveAndPrepare([
            JobFactory::KEY_SITE_ROOT_URL => $this->canonicalUrls[1],
            JobFactory::KEY_USER => $this->getTestUser(),
        ], [
            'prepare' => [
                HttpFixtureFactory::createStandardRobotsTxtResponse(),
                HttpFixtureFactory::createStandardSitemapResponse($this->canonicalUrls[1]),
            ],
        ]);
    }

    protected function getPostParameters()
    {
        return array(
            'user' => $this->jobs[0]->getUser()->getEmail()
        );
    }

    public function testListJobIdOrder()
    {
        $this->assertEquals($this->jobs[1]->getId(), $this->list->jobs[0]->id);
        $this->assertEquals($this->jobs[0]->getId(), $this->list->jobs[1]->id);
    }
}
