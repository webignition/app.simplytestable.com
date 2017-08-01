<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\ListAction\ExcludeFinished;

use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

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
        $userFactory = new UserFactory($this->container);

        return $userFactory->create();
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
        $jobFactory = new JobFactory($this->container);
        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create();

        // Non-crawling job
        $this->jobs[] = $jobFactory->createResolveAndPrepare([
            JobFactory::KEY_SITE_ROOT_URL => $this->canonicalUrls[0],
            JobFactory::KEY_USER => $user,
        ], [
            'prepare' => [
                HttpFixtureFactory::createStandardRobotsTxtResponse(),
                HttpFixtureFactory::createStandardSitemapResponse($this->canonicalUrls[0]),
            ],
        ]);

        // Crawling job
        $this->jobs[] = $jobFactory->createResolveAndPrepare([
            JobFactory::KEY_SITE_ROOT_URL => $this->canonicalUrls[1],
            JobFactory::KEY_USER => $user,
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
