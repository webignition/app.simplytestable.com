<?php

namespace SimplyTestable\ApiBundle\Tests\Services\TaskOutputJoiner;

use SimplyTestable\ApiBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\TaskControllerCompleteActionRequestFactory;

class LinkIntegrityTaskOutputJoinerServiceTest extends BaseSimplyTestableTestCase
{
    public function testJoinOnComplete()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->getJobService()->getById(
            $this->createResolveAndPrepareJob(self::DEFAULT_CANONICAL_URL, null, 'full site', array('Link integrity'))
        );
        $this->queueHttpFixtures(
            $this->buildHttpFixtureSet(
                $this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath($this->getName()). '/HttpResponses')
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
                    'state' => 200,
                    'type' => 'http',
                    'url' => 'http://example.com/three'
                )
            )),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ], [
            CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $tasks[0]->getType(),
            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => $tasks[0]->getUrl(),
            CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $tasks[0]->getParametersHash(),
        ]);

        $this->createTaskController($taskCompleteRequest)->completeAction();

        $this->executeCommand('simplytestable:task:assigncollection', array(
            'ids' => $tasks[1]->getId()
        ));

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
                    'context' => '<a href="http://example.com/four">Example Four</a>',
                    'state' => 404,
                    'type' => 'http',
                    'url' => 'http://example.com/four'
                )
            )),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ], [
            CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $tasks[1]->getType(),
            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => $tasks[1]->getUrl(),
            CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $tasks[1]->getParametersHash(),
        ]);

        $this->createTaskController($taskCompleteRequest)->completeAction();

        $this->assertEquals(2, $tasks[1]->getOutput()->getErrorCount());
    }

    public function testJoinGetsCorrectErrorCount()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->getJobService()->getById(
            $this->createResolveAndPrepareJob(self::DEFAULT_CANONICAL_URL, null, 'full site', array('Link integrity'))
        );
        $this->queueHttpFixtures(
            $this->buildHttpFixtureSet(
                $this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath($this->getName()). '/HttpResponses')
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

        $this->createTaskController($taskCompleteRequest)->completeAction();

        $this->executeCommand('simplytestable:task:assigncollection', array(
            'ids' => $tasks[1]->getId()
        ));

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
                    'context' => '<a href="http://example.com/four">Example Four</a>',
                    'state' => 404,
                    'type' => 'http',
                    'url' => 'http://example.com/four'
                )
            )),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 1,
            'warningCount' => 0
        ], [
            CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $tasks[1]->getType(),
            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => $tasks[1]->getUrl(),
            CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $tasks[1]->getParametersHash(),
        ]);

        $this->createTaskController($taskCompleteRequest)->completeAction();

        $this->assertEquals(2, $tasks[1]->getOutput()->getErrorCount());
    }
}
