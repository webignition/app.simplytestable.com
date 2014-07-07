<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\CountAction\ExcludeFinished;

class DoesNotIncludeCrawlJobsTest extends StateBasedTest {
    
    private $canonicalUrls = array(
        'http://crawling.example.com/',
    );

    protected function getExpectedCountValue() {
        return count($this->getCanonicalUrls());
    }

    protected function getCanonicalUrls() {
        return $this->canonicalUrls;
    }
    
    protected function createJobs() {        
        // Crawling job
        $this->jobs[] = $this->getJobService()->getById($this->createResolveAndPrepareCrawlJob(
            $this->canonicalUrls[0],
            $this->getTestUser()->getEmail()
        ));        
        
        
    }
    
    protected function getPostParameters() {
        return array(
            'user' => $this->jobs[0]->getUser()->getEmail()
        );
    }

}

