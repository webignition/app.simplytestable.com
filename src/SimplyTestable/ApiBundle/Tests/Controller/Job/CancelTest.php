<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class CancelTest extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }    
    
    public function testCancelAction() {        
        $canonicalUrl = 'http://example.com';        
        $jobId = $this->createJobAndGetId($canonicalUrl);
        
        $preCancelStatus = json_decode($this->getJobStatus($canonicalUrl, $jobId)->getContent())->state;
        $this->assertEquals('new', $preCancelStatus);
        
        $cancelResponse = $this->getJobController('cancelAction')->cancelAction($canonicalUrl, $jobId);
        $this->assertEquals(200, $cancelResponse->getStatusCode());
        
        $postCancelStatus = json_decode($this->getJobStatus($canonicalUrl, $jobId)->getContent())->state;
        $this->assertEquals('cancelled', $postCancelStatus);        
    }
    
    
    public function testCancelActionInMaintenanceReadOnlyModeReturns503() {
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));   
        $this->assertEquals(503, $this->getJobController('cancelAction')->cancelAction('http://example.com', 1)->getStatusCode());        
    }
    
    
    public function testCancelActionInMaintenanceBackupReadOnlyModeReturns503() {
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-backup-read-only'));   
        $this->assertEquals(503, $this->getJobController('cancelAction')->cancelAction('http://example.com', 1)->getStatusCode());        
    }
    
    
    public function testCancelParentJobCancelsParentJobAndCrawlJob() {
        $canonicalUrl = 'http://example.com';        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));
        
        $job->setState($this->getJobService()->getFailedNoSitemapState());
        $this->getJobService()->persistAndFlush($job);

        $this->getCrawlJobController('startAction')->startAction((string)$job->getWebsite(), $job->getId());       
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        
        $this->getJobController('cancelAction')->cancelAction($canonicalUrl, $crawlJobContainer->getParentJob()->getId());
        
        $this->assertTrue($crawlJobContainer->getParentJob()->getState()->equals($this->getJobService()->getCancelledState()));
        $this->assertTrue($crawlJobContainer->getCrawlJob()->getState()->equals($this->getJobService()->getCancelledState()));       
    }
    
    public function testCancelCrawlJobRestartsParentJob() {
        $canonicalUrl = 'http://example.com';        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));
        
        $job->setState($this->getJobService()->getFailedNoSitemapState());
        $this->getJobService()->persistAndFlush($job);

        $this->getCrawlJobController('startAction')->startAction((string)$job->getWebsite(), $job->getId());       
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        
        $crawlTask = $crawlJobContainer->getCrawlJob()->getTasks()->first();
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '["http:\/\/example.com\/one\/","http:\/\/example.com\/two\/","http:\/\/example.com\/three\/"]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$crawlTask->getUrl(), $crawlTask->getType()->getName(), $crawlTask->getParametersHash());         

        $this->getJobController('cancelAction')->cancelAction($canonicalUrl, $crawlJobContainer->getCrawlJob()->getId());
        
        $this->assertTrue($crawlJobContainer->getParentJob()->getState()->equals($this->getJobService()->getQueuedState()));
        $this->assertTrue($crawlJobContainer->getCrawlJob()->getState()->equals($this->getJobService()->getCancelledState()));                 
    }
    
}


