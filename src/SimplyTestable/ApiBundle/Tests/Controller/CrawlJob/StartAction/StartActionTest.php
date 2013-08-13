<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\CrawlJob\StartAction;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class StartActionTest extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }      
   
    public function testStartForInvalidTestReturns403() {
        $canonicalUrl = 'http://example.com';
        $jobId = 1;
        
        $response = $this->getCrawlJobController('startAction')->startAction($canonicalUrl, $jobId);
        $this->assertEquals(403, $response->getStatusCode());
    }
    
    public function testStartForInvalidTestStateReturns400() {
        $canonicalUrl = 'http://example.com';        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));

        $response = $this->getCrawlJobController('startAction')->startAction((string)$job->getWebsite(), $job->getId());
        $this->assertEquals(400, $response->getStatusCode());
    }    
    
    public function testStartWithNoExistingCrawlJob() {
        $canonicalUrl = 'http://example.com';        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));
        
        $job->setState($this->getJobService()->getFailedNoSitemapState());
        $this->getJobService()->persistAndFlush($job);

        $response = $this->getCrawlJobController('startAction')->startAction((string)$job->getWebsite(), $job->getId());
        $this->assertEquals(200, $response->getStatusCode());
    }    
    
    public function testStartWithExistingCrawlJob() {
        $canonicalUrl = 'http://example.com';        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));
        
        $job->setState($this->getJobService()->getFailedNoSitemapState());
        $this->getJobService()->persistAndFlush($job);
        
        $this->getCrawlJobService()->create($job);

        $response = $this->getCrawlJobController('startAction')->startAction((string)$job->getWebsite(), $job->getId());
        $this->assertEquals(200, $response->getStatusCode());
    }     
    
}


