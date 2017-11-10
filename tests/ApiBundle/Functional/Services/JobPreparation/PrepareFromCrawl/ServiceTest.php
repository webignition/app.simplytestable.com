<?php

namespace Tests\ApiBundle\Functional\Services\JobPreparation\PrepareFromCrawl;

use SimplyTestable\ApiBundle\Controller\TaskController;
use SimplyTestable\ApiBundle\Services\CrawlJobContainerService;
use SimplyTestable\ApiBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use SimplyTestable\ApiBundle\Services\UserService;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Tests\ApiBundle\Factory\HttpFixtureFactory;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\TaskControllerCompleteActionRequestFactory;

class ServiceTest extends AbstractBaseTestCase
{
    public function testCrawlJobAmmendmentsArePassedToParentJob()
    {
        $userService = $this->container->get(UserService::class);
        $userAccountPlanService = $this->container->get(UserAccountPlanService::class);

        $this->setUser($userService->getPublicUser());

        $userFactory = new UserFactory($this->container);

        $user = $userFactory->create();

        $jobFactory = new JobFactory($this->container);
        $job = $jobFactory->createResolveAndPrepare([
            JobFactory::KEY_SITE_ROOT_URL => 'http://example.com',
            JobFactory::KEY_USER => $user,
        ], [
            'prepare' => HttpFixtureFactory::createStandardCrawlPrepareResponses(),
        ]);

        $crawlJobContainerService = $this->container->get(CrawlJobContainerService::class);

        $crawlJobContainer = $crawlJobContainerService->getForJob($job);
        $urlDiscoveryTask = $crawlJobContainer->getCrawlJob()->getTasks()->first();

        $userAccountPlanPlan = $userAccountPlanService->getForUser($user)->getPlan();
        $urlLimit = $userAccountPlanPlan->getConstraintNamed('urls_per_job')->getLimit();

        $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode([
                'http://example.com/0/',
                'http://example.com/1/',
                'http://example.com/2/',
                'http://example.com/3/',
                'http://example.com/4/',
                'http://example.com/5/',
                'http://example.com/6/',
                'http://example.com/7/',
                'http://example.com/8/',
                'http://example.com/9/',
            ]),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ], [
            CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $urlDiscoveryTask->getType(),
            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => $urlDiscoveryTask->getUrl(),
            CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $urlDiscoveryTask->getParametersHash(),
        ]);

        $taskController = new TaskController();
        $taskController->setContainer($this->container);

        $this->container->get('request_stack')->push($taskCompleteRequest);
        $this->container->get('simplytestable.services.request.factory.task.complete')->init($taskCompleteRequest);

        $taskController->completeAction();

        $this->assertEquals(
            'plan-url-limit-reached:discovered-url-count-' . ($urlLimit + 1),
            $job->getAmmendments()->first()->getReason()
        );
    }
}
