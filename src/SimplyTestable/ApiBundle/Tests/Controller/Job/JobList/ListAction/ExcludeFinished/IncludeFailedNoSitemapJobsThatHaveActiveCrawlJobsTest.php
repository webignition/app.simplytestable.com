<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\ExcludeFinished;

use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class IncludeFailedNoSitemapJobsThatHaveActiveCrawlJobsTest extends StateBasedTest
{
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
        return array(self::DEFAULT_CANONICAL_URL);
    }

    protected function getExpectedJobListUrls()
    {
        return array_reverse($this->getCanonicalUrls());
    }

    protected function createJobs()
    {
        $this->jobs[] = $this->createJobFactory()->createResolveAndPrepare([
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

    public function testListContainsCrawlingParentJob()
    {
        $this->assertTrue($this->list->jobs[0]->id == $this->jobs[0]->getId());
    }
}
