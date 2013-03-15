<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class StartTest extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }       

    public function testStartAction() {
        $this->createPublicUserIfMissing();
        $jobController = $this->getJobStartController('startAction');        
        
        $canonicalUrls = array(
            'http://one.example.com',
            'http://two.example.com',
            'http://three.example.com'
        );
        
        foreach ($canonicalUrls as $urlIndex => $canonicalUrl) {
            $response = $jobController->startAction($canonicalUrl);
            $jobId = $this->getJobIdFromUrl($response->getTargetUrl());

            $this->assertEquals(302, $response->getStatusCode());        
            $this->assertInternalType('integer', $jobId);
            $this->assertGreaterThan(0, $jobId);
        }       
        
        return;
    }
    
    
    public function testStartForExistingJob() {
        $canonicalUrl = 'http://example.com/';
        
        $response1 = $this->createJob($canonicalUrl);
        $response2 = $this->createJob($canonicalUrl);
        $response3 = $this->createJob($canonicalUrl);
        
        $this->assertTrue($response1->getTargetUrl() === $response2->getTargetUrl());
        $this->assertTrue($response2->getTargetUrl() === $response3->getTargetUrl());        
    }
    
    
    public function testStartForExistingJobForDifferentUsers() {
        $canonicalUrl = 'http://example.com/';        
        $email1 = 'user1@example.com';
        $email2 = 'user2@example.com';
        
        $this->createAndActivateUser($email1, 'password1');
        $this->createAndActivateUser($email2, 'password1');
        
        $user1 = $this->getUserService()->findUserByEmail($email1);
        $user2 = $this->getUserService()->findUserByEmail($email2);
        
        $response1 = $this->createJob($canonicalUrl, $user1->getEmail());        
        $response2 = $this->createJob($canonicalUrl, $user2->getEmail());
        $response3 = $this->createJob($canonicalUrl, $user1->getEmail());        
        
        $this->assertTrue($response1->getTargetUrl() === $response3->getTargetUrl());
        $this->assertFalse($response1->getTargetUrl() === $response2->getTargetUrl());     
    }
    
    
    public function testStartActionInMaintenanceReadOnlyModeReturns503() {            
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));
        $this->assertEquals(503, $this->getJobStartController('startAction')->startAction('http://example.com')->getStatusCode());
    }    
    
    
    public function testStartActionInMaintenanceBackupReadOnlyModeReturns503() {            
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-backup-read-only'));
        $this->assertEquals(503, $this->getJobStartController('startAction')->startAction('http://example.com')->getStatusCode());
    }    
    
}


