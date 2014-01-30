<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\JobStart;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class StartTest extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }       

    public function testStartAction() {
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
        $this->assertTrue($job->getState()->equals($this->getJobService()->getQueuedState()));        
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
    
    
    public function testSingleUrlJobIsInstantlyPrepared() {
        $canonicalUrl = 'http://example.com/';        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl, null, 'single url'));                
        
        $this->assertTrue($job->getState()->equals($this->getJobService()->getQueuedState()));         
    }
    
    
    public function testPrepareWithCreditLimitReached() {
        $tasksPerJob = 30;
        $creditsPerMonth = 50;
        $jobsRequiredToExhaustCredits = (int)ceil($creditsPerMonth/$tasksPerJob);
        
        $email = 'user-basic@example.com';
        $password = 'password1';
        
        $this->createUser($email, $password);
        
        $this->getAccountPlanService()->find('basic')->getConstraintNamed('credits_per_month')->setLimit($creditsPerMonth);
        
        for ($jobIndex = 0; $jobIndex < $jobsRequiredToExhaustCredits; $jobIndex++) {
            $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));           
            $job = $this->getJobService()->getById($this->createAndPrepareJob('http://example.com/', $email));
            $this->getJobService()->getEntityManager()->refresh($job);
            $this->setJobTasksCompleted($job);
            $this->completeJob($job);            
        }
        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
                
        $job = $this->getJobService()->getById($this->createJobAndGetId('http://example.com/', $email));
        $rejectionReason = $this->getJobRejectionReasonService()->getForJob($job);
        
        $this->assertEquals('job-rejected', $job->getState()->getName());
        $this->assertEquals('plan-constraint-limit-reached', $rejectionReason->getReason());
        $this->assertEquals('credits_per_month', $rejectionReason->getConstraint()->getName());      
    }
    
    public function testSingleUrlJobJsStaticAnalysisIgnoreCommonCdns() {
        $canonicalUrl = 'http://example.com/';
        
        $jobId = $this->getJobIdFromUrl(
            $this->createJob(
                $canonicalUrl,
                null,
                'single url',
                array(
                    'JS static analysis'
                ),
                array(
                    'JS static analysis' => array(
                        'ignore-common-cdns' => 1
                    )
                )
             )->getTargetUrl()
        );
        
        $this->getJobService()->getEntityManager()->clear();
        
        $job = $this->getJobService()->getById($jobId);        
        $task = $job->getTasks()->first();
        
        $parametersObject = json_decode($task->getParameters());
        $this->assertTrue(count($parametersObject->{'domains-to-ignore'}) > 0);          
    }
    
    public function testStoreTaskTypeOptionsForTaskTypesThatHaveNotBeenSelected() {
        $canonicalUrl = 'http://example.com/';
        
        $jobId = $this->getJobIdFromUrl(
            $this->createJob(
                $canonicalUrl,
                null,
                'single url',
                array(
                    'JS static analysis'
                ),
                array(
                    'CSS validation' => array(
                        'ignore-common-cdns' => 1
                    )
                )
             )->getTargetUrl()
        );
        
        $this->getJobService()->getEntityManager()->clear();
        
        $job = $this->getJobService()->getById($jobId);
        
        $this->assertEquals(1, $job->getTaskTypeOptions()->count());
        
        /* @var $cssValidationTaskTypeOptions \SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions */
        $cssValidationTaskTypeOptions = $job->getTaskTypeOptions()->first();
        $this->assertEquals(array(
            'ignore-common-cdns' => 1
        ), $cssValidationTaskTypeOptions->getOptions());         
    }
    
    
    public function testRejectForUnroutableIpHost() {
        $canonicalUrl = 'http://127.0.0.1/';

        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));
        
        $jobControllerResponse = json_decode($this->getJobController('statusAction')->statusAction($job->getWebsite(), $job->getId())->getContent());
        
        $this->assertEquals('rejected', $jobControllerResponse->state);
        $this->assertEquals('unroutable', $jobControllerResponse->rejection->reason);    
    }    
    
    
    public function testRejectForUnroutableDomainHost() {
        $canonicalUrl = 'http://example/';

        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));
        
        $jobControllerResponse = json_decode($this->getJobController('statusAction')->statusAction($job->getWebsite(), $job->getId())->getContent());
        
        $this->assertEquals('rejected', $jobControllerResponse->state);
        $this->assertEquals('unroutable', $jobControllerResponse->rejection->reason);    
    }
    
    
    public function testWithParameters() {
        $canonicalUrl = 'http://example.com/';
        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl, null, null, null, null, array(
            'http-auth-username' => 'user',
            'http-auth-password' => 'pass'
        )));
        
        $this->assertEquals('{"http-auth-username":"user","http-auth-password":"pass"}', $job->getParameters());      
    }
    
    
    public function testWithSingleUrlTestAndHttpAuthParameters() {
        $canonicalUrl = 'http://example.com/';        
        
        $httpAuthUsernameKey = 'http-auth-username';
        $httpAuthPasswordKey = 'http-auth-password';
        $httpAuthUsernameValue = 'foo';
        $httpAuthPasswordValue = 'bar';
        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl, null, 'single url', array('html validation'), null, array(
            $httpAuthUsernameKey => $httpAuthUsernameValue,
            $httpAuthPasswordKey => $httpAuthPasswordValue            
        )));

        $decodedParameters = json_decode($job->getTasks()->first()->getParameters());
        $this->assertTrue(isset($decodedParameters->$httpAuthUsernameKey));
        $this->assertEquals($httpAuthUsernameValue, $decodedParameters->$httpAuthUsernameKey);
        $this->assertTrue(isset($decodedParameters->$httpAuthPasswordKey));
        $this->assertEquals($httpAuthPasswordValue, $decodedParameters->$httpAuthPasswordKey);
           
    }    
    
}