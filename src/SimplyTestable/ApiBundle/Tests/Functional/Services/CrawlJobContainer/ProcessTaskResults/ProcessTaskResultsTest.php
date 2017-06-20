<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\CrawlJobContainer\ProcessTaskResults;

use SimplyTestable\ApiBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\TaskControllerCompleteActionRequestFactory;

class ProcessTaskResultsTest extends BaseSimplyTestableTestCase
{

    public function testWithUrlsNotYetProcessed()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $job = $this->createJobFactory()->createResolveAndPrepare([], [
            'prepare' => HttpFixtureFactory::createStandardCrawlPrepareResponses(),
        ]);

        $this->queueHttpFixtures(
            $this->buildHttpFixtureSet(
                $this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')
            )
        );

        $this->createWorker();

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);

        $task = $crawlJobContainer->getCrawlJob()->getTasks()->first();
        $this->executeCommand('simplytestable:task:assigncollection', array(
            'ids' => $task->getId()
        ));

        $this->assertEquals($this->getTaskService()->getInProgressState(), $task->getState());

        $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '["http:\/\/example.com\/one\/","http:\/\/example.com\/two\/","http:\/\/example.com\/three\/"]',
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
        $response = $taskController->completeAction();

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertTrue($this->getCrawlJobContainerService()->processTaskResults($task));
        $this->assertEquals(4, $crawlJobContainer->getCrawlJob()->getTasks()->count());
    }

    public function testWithAllUrlsAlreadyProcessed()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $job = $this->createJobFactory()->createResolveAndPrepare([], [
            'prepare' => HttpFixtureFactory::createStandardCrawlPrepareResponses(),
        ]);

        $this->queueHttpFixtures(
            $this->buildHttpFixtureSet(
                $this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')
            )
        );

        $this->createWorker();

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);

        $task = $crawlJobContainer->getCrawlJob()->getTasks()->first();
        $this->executeCommand('simplytestable:task:assigncollection', array(
            'ids' => $task->getId()
        ));

        $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '["http:\/\/example.com\/one\/"]',
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

        $this->getCrawlJobContainerService()->processTaskResults($task);
        $this->assertEquals(2, $crawlJobContainer->getCrawlJob()->getTasks()->count());

        $task = $crawlJobContainer->getCrawlJob()->getTasks()->get(1);
        $this->executeCommand('simplytestable:task:assigncollection', array(
            'ids' => $task->getId()
        ));

        $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '["http:\/\/example.com\/one\/"]',
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

        $this->getCrawlJobContainerService()->processTaskResults($task);

        $this->assertEquals(2, $crawlJobContainer->getCrawlJob()->getTasks()->count());

        foreach ($crawlJobContainer->getCrawlJob()->getTasks() as $task) {
            $this->assertEquals('task-completed', $task->getState()->getName());
        }
    }
}
