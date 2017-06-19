<?php

namespace SimplyTestable\ApiBundle\Tests\Services\CrawlJobContainer\ProcessTaskResults\AccountPlanConstraint;

use SimplyTestable\ApiBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\TaskControllerCompleteActionRequestFactory;

class PublicPlanTest extends BaseSimplyTestableTestCase
{
    public function testWithConstraintHitOnFirstResultSet()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultCrawlJob());
        $this->queueHttpFixtures(
            $this->buildHttpFixtureSet(
                $this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')
            )
        );

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);

        $task = $crawlJobContainer->getCrawlJob()->getTasks()->first();
        $this->executeCommand('simplytestable:task:assigncollection', array(
            'ids' => $task->getId()
        ));

        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($job->getUser());
        $numberOfUrlsToDiscover = $userAccountPlan->getPlan()->getConstraintNamed('urls_per_job')->getLimit() * 2;

        $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet(self::DEFAULT_CANONICAL_URL, $numberOfUrlsToDiscover)),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ], [
            CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $task->getType(),
            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => $task->getUrl(),
            CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $task->getParametersHash(),
        ]);

        $taskController = $this->createControllerFactory()->createTaskController($taskCompleteRequest);
        $taskController->completeAction();

        $this->assertCount(
            $userAccountPlan->getPlan()->getConstraintNamed('urls_per_job')->getLimit(),
            $this->getCrawlJobContainerService()->getDiscoveredUrls($crawlJobContainer, true)
        );
    }

    public function testWithConstraintHitOnSecondResultSet()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultCrawlJob());
        $this->queueHttpFixtures(
            $this->buildHttpFixtureSet(
                $this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')
            )
        );

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);

        $task = $crawlJobContainer->getCrawlJob()->getTasks()->first();
        $this->executeCommand('simplytestable:task:assigncollection', array(
            'ids' => $task->getId()
        ));

        $userAccountPlanPlan = $this->getUserAccountPlanService()->getForUser($job->getUser())->getPlan();
        $numberOfUrlsToDiscover = (int)round($userAccountPlanPlan->getConstraintNamed('urls_per_job')->getLimit() / 2);

        $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet(self::DEFAULT_CANONICAL_URL, $numberOfUrlsToDiscover)),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ], [
            CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $task->getType(),
            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => $task->getUrl(),
            CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $task->getParametersHash(),
        ]);

        $taskController = $this->createControllerFactory()->createTaskController($taskCompleteRequest);
        $taskController->completeAction();

        $task = $crawlJobContainer->getCrawlJob()->getTasks()->get(1);
        $this->executeCommand('simplytestable:task:assigncollection', array(
            'ids' => $task->getId()
        ));

        $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet(
                self::DEFAULT_CANONICAL_URL,
                $numberOfUrlsToDiscover,
                $numberOfUrlsToDiscover
            )),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ], [
            CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $task->getType(),
            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => $task->getUrl(),
            CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $task->getParametersHash(),
        ]);

        $taskController = $this->createControllerFactory()->createTaskController($taskCompleteRequest);
        $taskController->completeAction();

        $this->assertCount(
            $userAccountPlanPlan->getConstraintNamed('urls_per_job')->getLimit(),
            $this->getCrawlJobContainerService()->getDiscoveredUrls($crawlJobContainer, true)
        );
    }

    public function testCrawlJobHasAmmendmentAddedIfDiscoveredUrlSetIsConstrainedByAccountPlan()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultCrawlJob());
        $this->queueHttpFixtures(
            $this->buildHttpFixtureSet(
                $this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')
            )
        );

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);

        $task = $crawlJobContainer->getCrawlJob()->getTasks()->first();
        $this->executeCommand('simplytestable:task:assigncollection', array(
            'ids' => $task->getId()
        ));

        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($job->getUser());

        $numberOfUrlsToDiscover = $userAccountPlan->getPlan()->getConstraintNamed('urls_per_job')->getLimit() * 2;

        $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet(self::DEFAULT_CANONICAL_URL, $numberOfUrlsToDiscover)),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ], [
            CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $task->getType(),
            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => $task->getUrl(),
            CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $task->getParametersHash(),
        ]);

        $taskController = $this->createControllerFactory()->createTaskController($taskCompleteRequest);
        $taskController->completeAction();

        $this->assertCount(1, $crawlJobContainer->getCrawlJob()->getAmmendments());

        /* @var $ammendment \SimplyTestable\ApiBundle\Entity\Job\Ammendment */
        $ammendment = $crawlJobContainer->getCrawlJob()->getAmmendments()->first();
        $this->assertEquals('plan-url-limit-reached:discovered-url-count-21', $ammendment->getReason());
    }
}
