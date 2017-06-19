<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Start\StartAction;

use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use Symfony\Component\HttpFoundation\Request;

class StartTest extends ActionTest
{
    public function testFullSiteRejectionDoesNotAffectSingleUrlJobStart()
    {
        $canonicalUrl = 'http://example.com/';
        $jobFactory = $this->createJobFactory();
        $jobFactory->create();

        $request = new Request([], [
            'type' => JobTypeService::SINGLE_URL_NAME,
        ], [
            'site_root_url' => $canonicalUrl,
        ]);
        $jobStartController = $this->createControllerFactory()->createJobStartController($request);
        $jobStartResponse = $jobStartController->startAction($request, $canonicalUrl);

        $job = $this->getJobFromResponse($jobStartResponse);
        $this->assertEquals($this->getJobService()->getStartingState(), $job->getState());
    }

    public function testSingleUrlRejectionDoesNotAffectFullSiteJobStart()
    {
        $canonicalUrl = 'http://example.com/';

        $user = $this->getUserService()->getPublicUser();
        $this->getUserService()->setUser($user);

        $job = $this->createJobFactory()->create([
            JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
            JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
        ]);

        $this->cancelJob($job);

        $request = new Request([], [
            'type' => JobTypeService::FULL_SITE_NAME,
        ], [
            'site_root_url' => $canonicalUrl,
        ]);
        $jobStartController = $this->createControllerFactory()->createJobStartController($request);
        $jobStartResponse = $jobStartController->startAction($request, $canonicalUrl);

        $job = $this->getJobFromResponse($jobStartResponse);
        $this->assertEquals($this->getJobService()->getStartingState(), $job->getState());
    }

    public function testSingleUrlJobJsStaticAnalysisIgnoreCommonCdns()
    {
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

    public function testStoreTaskTypeOptionsForTaskTypesThatHaveNotBeenSelected()
    {
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

    public function testWithParameters()
    {
        $canonicalUrl = 'http://example.com/';

        $request = new Request([], [
            'type' => JobTypeService::FULL_SITE_NAME,
            'parameters' => [
                'http-auth-username' => 'user',
                'http-auth-password' => 'pass'
            ],
        ], [
            'site_root_url' => $canonicalUrl,
        ]);
        $jobStartController = $this->createControllerFactory()->createJobStartController($request);
        $jobStartResponse = $jobStartController->startAction($request, $canonicalUrl);

        $job = $this->getJobFromResponse($jobStartResponse);
        $this->assertEquals('{"http-auth-username":"user","http-auth-password":"pass"}', $job->getParameters());
    }

    public function testWithSingleUrlTestAndHttpAuthParameters()
    {
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
