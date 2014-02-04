<?php

namespace SimplyTestable\ApiBundle\Tests\Services\CrawlJobContainer;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class PrepareTest extends BaseSimplyTestableTestCase {

    public function testForInProgressState() {        
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultCrawlJob());
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $crawlJobContainer->getCrawlJob()->setState($this->getJobService()->getInProgressState());
        
        $this->assertFalse($this->getCrawlJobContainerService()->prepare($crawlJobContainer));
    }
    
    public function testForCompletedState() {        
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultCrawlJob());
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $crawlJobContainer->getCrawlJob()->setState($this->getJobService()->getCompletedState());
        
        $this->assertFalse($this->getCrawlJobContainerService()->prepare($crawlJobContainer));
    }    
    
    public function testForQueuedState() {        
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultCrawlJob());
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        
        $this->assertTrue($this->getCrawlJobContainerService()->prepare($crawlJobContainer));        
        $this->assertEquals(1, $crawlJobContainer->getCrawlJob()->getTasks()->count());      
        $this->assertEquals('URL discovery', $crawlJobContainer->getCrawlJob()->getTasks()->first()->getType()->getName());
        $this->assertEquals('job-queued', $crawlJobContainer->getCrawlJob()->getState()->getName());
        $this->assertNotNull($crawlJobContainer->getCrawlJob()->getTimePeriod());
    } 
    
    public function testPrepareIsIdempotent() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultCrawlJob());
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        
        $this->assertTrue($this->getCrawlJobContainerService()->prepare($crawlJobContainer));
        $this->assertTrue($this->getCrawlJobContainerService()->prepare($crawlJobContainer));              
        $this->assertEquals(1, $crawlJobContainer->getCrawlJob()->getTasks()->count());      
        $this->assertEquals('URL discovery', $crawlJobContainer->getCrawlJob()->getTasks()->first()->getType()->getName());        
    }
    
    public function testUrlDiscoveryTaskHasWwwAndNonWwwScope() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultCrawlJob());
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        
        $this->assertTrue($this->getCrawlJobContainerService()->prepare($crawlJobContainer));             
        $this->assertEquals('{"scope":["http:\/\/example.com\/","http:\/\/www.example.com\/"]}', $crawlJobContainer->getCrawlJob()->getTasks()->first()->getParameters());       
    }    
    
    public function testUrlDiscoveryTaskHasNonWwwAndWwwScope() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultCrawlJob());
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        
        $this->assertTrue($this->getCrawlJobContainerService()->prepare($crawlJobContainer));             
        $this->assertEquals('{"scope":["http:\/\/example.com\/","http:\/\/www.example.com\/"]}', $crawlJobContainer->getCrawlJob()->getTasks()->first()->getParameters());       
    }        

}
