<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\TasksAction;

use SimplyTestable\ApiBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\TaskControllerCompleteActionRequestFactory;

class TasksActionTest extends BaseControllerJsonTestCase
{
    public function testNoOutputForIncompleteTasksWithPartialOutput()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $job = $this->createJobFactory()->createResolveAndPrepare([
            JobFactory::KEY_TEST_TYPES => ['link integrity'],
        ]);

        $this->queueHttpFixtures(
            $this->buildHttpFixtureSet(
                $this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')
            )
        );
        $tasks = $job->getTasks();

        $now = new \DateTime();

        $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
            'end_date_time' => $now->format('Y-m-d H:i:s'),
            'output' => json_encode(array(
                array(
                    'context' => '<a href="http://example.com/one">Example One</a>',
                    'state' => 404,
                    'type' => 'http',
                    'url' => 'http://example.com/one'
                ),
                array(
                    'context' => '<a href="http://example.com/two">Example Two</a>',
                    'state' => 200,
                    'type' => 'http',
                    'url' => 'http://example.com/two'
                ),
                array(
                    'context' => '<a href="http://example.com/three">Example Three</a>',
                    'state' => 204,
                    'type' => 'http',
                    'url' => 'http://example.com/three'
                )
            )),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 1,
            'warningCount' => 0
        ], [
            CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $tasks[0]->getType(),
            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => $tasks[0]->getUrl(),
            CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $tasks[0]->getParametersHash(),
        ]);

        $taskController = $this->createControllerFactory()->createTaskController($taskCompleteRequest);
        $taskController->completeAction();

        $this->executeCommand('simplytestable:task:assigncollection', array(
            'ids' => $tasks[1]->getId()
        ));

        $tasksActionResponse = $this->getJobController('tasksAction')->tasksAction(
            $job->getWebsite()->getCanonicalUrl(),
            $job->getId()
        );

        $tasksResponseObject = json_decode($tasksActionResponse->getContent());

        foreach ($tasksResponseObject as $taskResponse) {
            if ($taskResponse->id == $tasks[0]->getId()) {
                $this->assertTrue(isset($taskResponse->output));
            } else {
                $this->assertFalse(isset($taskResponse->output));
            }
        }
    }

    public function testFailedNoRetryAvailableTaskOutputIsReturned()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $job = $this->createJobFactory()->createResolveAndPrepare();

        foreach ($job->getTasks() as $task) {
            $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
                'end_date_time' => '2012-03-08 17:03:00',
                'output' => '{"messages":[]}',
                'contentType' => 'application/json',
                'state' => 'task-failed-no-retry-available',
                'errorCount' => 1,
                'warningCount' => 0
            ], [
                CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $task->getType(),
                CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => $task->getUrl(),
                CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $task->getParametersHash(),
            ]);

            $taskController = $this->createControllerFactory()->createTaskController($taskCompleteRequest);
            $taskController->completeAction();
        }

        $tasksActionResponse = $this->getJobController('tasksAction')->tasksAction(
            $job->getWebsite()->getCanonicalUrl(),
            $job->getId()
        );

        $tasksResponseObject = json_decode($tasksActionResponse->getContent());

        foreach ($tasksResponseObject as $taskResponse) {
            $this->assertTrue(isset($taskResponse->output));
        }
    }
}
