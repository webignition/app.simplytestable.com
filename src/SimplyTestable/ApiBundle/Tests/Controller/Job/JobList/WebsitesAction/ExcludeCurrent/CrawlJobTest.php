<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\WebsitesAction\ExcludeCurrent;

class CrawlJobTest extends ExcludeCurrentTest {    
    
    protected function getCanonicalUrls() {
        return array(self::DEFAULT_CANONICAL_URL);
    }
    
    protected function getExpectedWebsitesList() {
        return array();
    }
    
    protected function createJobs() {
        $this->jobs[] = $this->getJobService()->getById($this->createResolveAndPrepareCrawlJob(self::DEFAULT_CANONICAL_URL, $this->getTestUser()->getEmail()));
    }    

}