<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Start;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class RejectTest extends BaseControllerJsonTestCase {   
    
    public function testRejectDueToPlanFullSiteConstraint() {
        $canonicalUrl = 'http://example.com/';
        
        $user = $this->getUserService()->getPublicUser();
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);
        
        $constraint = $userAccountPlan->getPlan()->getConstraintNamed('full_site_jobs_per_site');        
        $constraintLimit = $constraint->getLimit();
        
        for ($i = 0; $i < $constraintLimit; $i++) {
            $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));            
            $this->cancelJob($job);            
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
            $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl, null, 'single url'));            
            $this->cancelJob($job);
        }
        
        $rejectedJobId = $this->createJobAndGetId($canonicalUrl, null, 'single url');
        $rejectedJob = $this->getJobService()->getById($rejectedJobId);
        
        $this->assertTrue($rejectedJob->getState()->equals($this->getJobService()->getRejectedState()));        
    }
    
    
    public function testRejectForUnroutableIpHost() {
        $canonicalUrl = 'http://127.0.0.1/';

        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));
        $jobObject = $this->fetchJobStatusObject($job);
        
        $this->assertEquals('rejected', $jobObject->state);
        $this->assertEquals('unroutable', $jobObject->rejection->reason);    
    }    
    
    
    public function testRejectForUnroutableDomainHost() {
        $canonicalUrl = 'http://example/';

        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));
        
        $jobObject = $this->fetchJobStatusObject($job);
        
        $this->assertEquals('rejected', $jobObject->state);
        $this->assertEquals('unroutable', $jobObject->rejection->reason);    
    } 
    
    
    public function testRejectWithCreditLimitReached() {
        $tasksPerJob = 12;
        $creditsPerMonth = 50;
        $jobsRequiredToExhaustCredits = (int)ceil($creditsPerMonth/$tasksPerJob);
        
        $email = 'user-basic@example.com';
        $password = 'password1';
        
        $this->createUser($email, $password);
        
        $this->getAccountPlanService()->find('basic')->getConstraintNamed('credits_per_month')->setLimit($creditsPerMonth);
        
        for ($jobIndex = 0; $jobIndex < $jobsRequiredToExhaustCredits; $jobIndex++) {            
            $job = $this->getJobService()->getById($this->createResolveAndPrepareJob(self::DEFAULT_CANONICAL_URL, $this->getTestUser()->getEmail()));            
            $this->setJobTasksCompleted($job);
            $this->completeJob($job);
        }
        
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::DEFAULT_CANONICAL_URL, $this->getTestUser()->getEmail()));

        $rejectionReason = $this->getJobRejectionReasonService()->getForJob($job);
        
        $this->assertEquals('job-rejected', $job->getState()->getName());
        $this->assertEquals('plan-constraint-limit-reached', $rejectionReason->getReason());
        $this->assertEquals('credits_per_month', $rejectionReason->getConstraint()->getName());      
    }    
    
}