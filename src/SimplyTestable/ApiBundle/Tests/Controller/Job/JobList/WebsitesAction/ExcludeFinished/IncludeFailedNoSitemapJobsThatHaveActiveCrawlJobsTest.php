<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\WebsitesAction\ExcludeFinished;

class IncludeFailedNoSitemapJobsThatHaveActiveCrawlJobsTest extends StateBasedTest {

    protected function getExpectedWebsitesList() {
        return $this->getCanonicalUrls();
    }

    protected function getCanonicalUrls() {
        return array(self::DEFAULT_CANONICAL_URL);
    }
    
    protected function createJobs() {
        $this->jobs[] = $this->getJobService()->getById($this->createResolveAndPrepareCrawlJob(self::DEFAULT_CANONICAL_URL, $this->getTestUser()->getEmail()));
    }
    
    protected function getPostParameters() {
        return array(
            'user' => $this->jobs[0]->getUser()->getEmail()
        );
    }

}


