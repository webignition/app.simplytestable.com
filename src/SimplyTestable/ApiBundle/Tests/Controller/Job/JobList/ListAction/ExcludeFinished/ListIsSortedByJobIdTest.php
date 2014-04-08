<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\ExcludeFinished;

class ListIsSortedByJobIdTest extends StateBasedTest {
    
    private $canonicalUrls = array(
        'http://non-crawling.example.com/',
        'http://crawling.example.com/',
    );

    protected function getExpectedListLength() {
        return count($this->getCanonicalUrls());
    }

    protected function getCanonicalUrls() {
        return $this->canonicalUrls;
    }

    protected function getExpectedJobListUrls() {
        return array_reverse($this->getCanonicalUrls());
    }
    
    protected function createJobs() {
        // Non-crawling job
        $this->jobs[] = $this->getJobService()->getById($this->createResolveAndPrepareJob(
            $this->canonicalUrls[0],
            $this->getTestUser()->getEmail()
        ));
        
        // Crawling job
        $this->jobs[] = $this->getJobService()->getById($this->createResolveAndPrepareCrawlJob(
            $this->canonicalUrls[1],
            $this->getTestUser()->getEmail()
        ));        
        
        
    }
    
    protected function getPostParameters() {
        return array(
            'user' => $this->jobs[0]->getUser()->getEmail()
        );
    }
    
    public function testListJobIdOrder() {        
        $this->assertEquals($this->jobs[1]->getId(), $this->list->jobs[0]->id);        
        $this->assertEquals($this->jobs[0]->getId(), $this->list->jobs[1]->id);        
    }

}


