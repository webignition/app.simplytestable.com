<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Start\StartAction;

class StartTest extends ActionTest {
    
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
        $this->getUserService()->setUser($user);
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
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

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
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

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
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

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