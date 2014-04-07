<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Start\StartAction;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class StartTest extends BaseControllerJsonTestCase {      

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
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
        $this->assertEquals(503, $this->getJobStartController('startAction')->startAction('http://example.com')->getStatusCode());
    }    
    
    
    public function testStartActionInMaintenanceBackupReadOnlyModeReturns503() {            
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
        $this->assertEquals(503, $this->getJobStartController('startAction')->startAction('http://example.com')->getStatusCode());
    } 
    
    
    public function testFullSiteRejectionDoesNotAffectSingleUrlJobStart() {
        $canonicalUrl = 'http://example.com/';
        
        $user = $this->getUserService()->getPublicUser();
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);
        
        $constraint = $userAccountPlan->getPlan()->getConstraintNamed('full_site_jobs_per_site');        
        $constraintLimit = $constraint->getLimit();
        
        for ($i = 0; $i < $constraintLimit; $i++) {
            $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));                    
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
            $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl, null, 'single url'));            
            $this->cancelJob($job);            
        }
        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl, null));        
        $this->assertTrue($job->getState()->equals($this->getJobService()->getStartingState()));        
    }

    
    public function testSingleUrlJobJsStaticAnalysisIgnoreCommonCdns() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareJob(
            self::DEFAULT_CANONICAL_URL,
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
        ));
        

        $task = $job->getTasks()->first();        
        $parametersObject = json_decode($task->getParameters());
        $this->assertTrue(count($parametersObject->{'domains-to-ignore'}) > 0);          
    }
    
    public function testStoreTaskTypeOptionsForTaskTypesThatHaveNotBeenSelected() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareJob(
            self::DEFAULT_CANONICAL_URL,
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
        ));
        
        $this->assertEquals(1, $job->getTaskTypeOptions()->count());
        
        /* @var $cssValidationTaskTypeOptions \SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions */
        $cssValidationTaskTypeOptions = $job->getTaskTypeOptions()->first();
        $this->assertEquals(array(
            'ignore-common-cdns' => 1
        ), $cssValidationTaskTypeOptions->getOptions());         
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
        $httpAuthUsernameKey = 'http-auth-username';
        $httpAuthPasswordKey = 'http-auth-password';
        $httpAuthUsernameValue = 'foo';
        $httpAuthPasswordValue = 'bar';        
        
        $job = $this->getJobService()->getById($this->createResolveAndPrepareJob(
                self::DEFAULT_CANONICAL_URL,
                null,
                'single url',
                array('html validation'),
                null,
                array(
                    $httpAuthUsernameKey => $httpAuthUsernameValue,
                    $httpAuthPasswordKey => $httpAuthPasswordValue
                )
        ));

        $decodedParameters = json_decode($job->getTasks()->first()->getParameters());
        $this->assertTrue(isset($decodedParameters->$httpAuthUsernameKey));
        $this->assertEquals($httpAuthUsernameValue, $decodedParameters->$httpAuthUsernameKey);
        $this->assertTrue(isset($decodedParameters->$httpAuthPasswordKey));
        $this->assertEquals($httpAuthPasswordValue, $decodedParameters->$httpAuthPasswordKey);
           
    }    
    
}