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
        
        $decodedParameters = $task->getParametersArray();
        $this->assertTrue(isset($decodedParameters['cookies']));
        $this->assertEquals($this->cookies, $decodedParameters['cookies']);         
    }      

}
