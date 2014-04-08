<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\ExcludeCurrent;

class CrawlJobTest extends ExcludeCurrentTest {    
    
    protected function getCanonicalUrls() {
        return array(self::DEFAULT_CANONICAL_URL);
    }

    protected function getExpectedJobListUrls() {
        return array();
    }

    protected function getExpectedListLength() {
        return 0;
    }
    
    protected function createJobs() {
        $this->jobs[] = $this->getJobService()->getById($this->createResolveAndPrepareCrawlJob(self::DEFAULT_CANONICAL_URL, $this->getTestUser()->getEmail()));
    }    

}