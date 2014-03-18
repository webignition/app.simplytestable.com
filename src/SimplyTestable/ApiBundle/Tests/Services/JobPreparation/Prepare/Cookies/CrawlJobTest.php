<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\Cookies;

class CrawlJobTest extends ServiceTest {    
    
    public function setUp() {
        parent::setUp();       
        
        $this->queuePrepareHttpFixturesForCrawlJob($this->job->getWebsite()->getCanonicalUrl());        
        $this->getJobPreparationService()->prepare($this->job);
    }
    
    
    public function testCrawlJobTaskTakesCookieParameters() {         
        $crawlJob = $this->getCrawlJobContainerService()->getForJob($this->job)->getCrawlJob();
        $task = $crawlJob->getTasks()->first();
        
        $decodedParameters = json_decode($task->getParameters());            
        $this->assertTrue(isset($decodedParameters->cookies));
            
        $decodedCookies = json_decode($decodedParameters->cookies, true);
        $this->assertEquals($this->cookies, $decodedCookies);         
    }      

}
