<?php

namespace SimplyTestable\ApiBundle\Tests\Services\CrawlJobContainer;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class PrepareTest extends BaseSimplyTestableTestCase {

    public function testForInProgressState() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl));
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $crawlJobContainer->getCrawlJob()->setState($this->getJobService()->getInProgressState());
        
        $this->assertFalse($this->getCrawlJobContainerService()->prepare($crawlJobContainer));
    }
    
    public function testForCompletedState() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl));
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $crawlJobContainer->getCrawlJob()->setState($this->getJobService()->getCompletedState());
        
        $this->assertFalse($this->getCrawlJobContainerService()->prepare($crawlJobContainer));
    }    
    
    public function testForQueuedState() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl));
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        
        $this->assertTrue($this->getCrawlJobContainerService()->prepare($crawlJobContainer));
        
        $this->assertEquals(1, $crawlJobContainer->getCrawlJob()->getTasks()->count());      
        $this->assertEquals('URL discovery', $crawlJobContainer->getCrawlJob()->getTasks()->first()->getType()->getName());
        $this->assertEquals('job-queued', $crawlJobContainer->getCrawlJob()->getState()->getName());
        $this->assertNotNull($crawlJobContainer->getCrawlJob()->getTimePeriod());
    } 
    
    public function testPrepareIsIdempotent() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl));
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        
        $this->assertTrue($this->getCrawlJobContainerService()->prepare($crawlJobContainer));
        $this->assertTrue($this->getCrawlJobContainerService()->prepare($crawlJobContainer));      
        
        $this->assertEquals(1, $crawlJobContainer->getCrawlJob()->getTasks()->count());      
        $this->assertEquals('URL discovery', $crawlJobContainer->getCrawlJob()->getTasks()->first()->getType()->getName());        
    }

}
