<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\PrepareFromCrawl;

use SimplyTestable\ApiBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\TaskControllerCompleteActionRequestFactory;

class ServiceTest extends BaseSimplyTestableTestCase
{
    public function testCrawlJobAmmendmentsArePassedToParentJob()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->getJobService()->getById(
            $this->createResolveAndPrepareCrawlJob(self::DEFAULT_CANONICAL_URL, $this->getTestUser()->getEmail())
        );

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $urlDiscoveryTask = $crawlJobContainer->getCrawlJob()->getTasks()->first();

        $userAccountPlanPlan = $this->getUserAccountPlanService()->getForUser($this->getTestUser())->getPlan();

        $urlLimit = $userAccountPlanPlan->getConstraintNamed('urls_per_job')->getLimit();

        $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet(self::DEFAULT_CANONICAL_URL, 1)),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ], [
            CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $urlDiscoveryTask->getType(),
            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => $urlDiscoveryTask->getUrl(),
            CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $urlDiscoveryTask->getParametersHash(),
        ]);

        $this->createTaskController($taskCompleteRequest)->completeAction();

        $this->assertEquals(
            'plan-url-limit-reached:discovered-url-count-' . ($urlLimit + 1),
            $job->getAmmendments()->first()->getReason()
        );
    }
}
