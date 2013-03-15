<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class CancelTest extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }    
    
    public function testCancelAction() {
        $this->createPublicUserIfMissing();
        $this->removeAllJobs();
        
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
    
}


