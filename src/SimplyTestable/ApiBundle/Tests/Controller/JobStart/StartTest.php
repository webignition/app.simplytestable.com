<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\JobStart;

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
    
    
    public function testRejectDueToPlanFullSiteConstraint() {
        $canonicalUrl = 'http://example.com/';
        
        $user = $this->getUserService()->getPublicUser();
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);
        
        $constraint = $userAccountPlan->getPlan()->getConstraintNamed('full_site_jobs_per_site');        
        $constraintLimit = $constraint->getLimit();
        
        for ($i = 0; $i < $constraintLimit; $i++) {
            $this->cancelJob($canonicalUrl, $this->createJobAndGetId($canonicalUrl));            
        }
        
        $rejectedJobId = $this->createJobAndGetId($canonicalUrl);
        $rejectedJob = $this->getJobService()->getById($rejectedJobId);
        
        $this->assertTrue($rejectedJob->getState()->equals($this->getJobService()->getRejectedState()));
    }
    
    public function testRejectDueToPlanSingleUrlConstraint() {
        $canonicalUrl = 'http://example.com/';
        
        $user = $this->getUserService()->getPublicUser();
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);
        
        $constraint = $userAccountPlan->getPlan()->getConstraintNamed('single_url_jobs_per_url');        
        $constraintLimit = $constraint->getLimit();
        
        for ($i = 0; $i < $constraintLimit; $i++) {
            $this->cancelJob($canonicalUrl, $this->createJobAndGetId($canonicalUrl, null, 'single url'));            
        }
        
        $rejectedJobId = $this->createJobAndGetId($canonicalUrl, null, 'single url');
        $rejectedJob = $this->getJobService()->getById($rejectedJobId);
        
        $this->assertTrue($rejectedJob->getState()->equals($this->getJobService()->getRejectedState()));        
    } 
    
    
    public function testFullSiteRejectionDoesNotAffectSingleUrlJobStart() {
        $canonicalUrl = 'http://example.com/';
        
        $user = $this->getUserService()->getPublicUser();
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);
        
        $constraint = $userAccountPlan->getPlan()->getConstraintNamed('full_site_jobs_per_site');        
        $constraintLimit = $constraint->getLimit();
        
        for ($i = 0; $i < $constraintLimit; $i++) {
            $this->cancelJob($canonicalUrl, $this->createJobAndGetId($canonicalUrl));            
        }
        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl, null, 'single url'));        
        $this->assertTrue($job->getState()->equals($this->getJobService()->getStartingState()));        
    }
    
    
    public function testSingleUrlRejectionDoesNotAffectFullSiteJobStart() {
        $canonicalUrl = 'http://example.com/';
        
        $user = $this->getUserService()->getPublicUser();
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);
        
        $constraint = $userAccountPlan->getPlan()->getConstraintNamed('full_site_jobs_per_site');        
        $constraintLimit = $constraint->getLimit();
        
        for ($i = 0; $i < $constraintLimit; $i++) {
            $this->cancelJob($canonicalUrl, $this->createJobAndGetId($canonicalUrl, null, 'single url'));            
        }
        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl, null));        
        $this->assertTrue($job->getState()->equals($this->getJobService()->getStartingState()));        
    }    
    
    
    
}


