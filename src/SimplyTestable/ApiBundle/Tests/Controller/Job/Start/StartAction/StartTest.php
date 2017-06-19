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

    public function testStoreTaskTypeOptionsForTaskTypesThatHaveNotBeenSelected()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $request = new Request([], [
            'test-types' => ['js static analysis'],
            'test-type-options' => [
                'js static analysis' => [
                    'ignore-common-cdns' => 1
                ],
            ],
        ], [
            'site_root_url' => self::DEFAULT_CANONICAL_URL,
        ]);

        $jobStartController = $this->createControllerFactory()->createJobStartController($request);
        $startResponse = $jobStartController->startAction($request, self::DEFAULT_CANONICAL_URL);
        $job = $this->getJobFromResponse($startResponse);

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
}
