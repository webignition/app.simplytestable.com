<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\TaskPreProcessor\LinkIntegrity\ErrorRetrievingTestContent;

use Doctrine\ORM\PersistentCollection;
use SimplyTestable\ApiBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\TaskControllerCompleteActionRequestFactory;

abstract class PreprocessorTest extends BaseSimplyTestableTestCase
{
    private $taskOutputContent = array(
        array(
            'context' => '<a href="http://example.com/one">Example One</a>',
            'state' => 200,
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
    );

    /**
     * @var PersistentCollection
     */
    private $tasks = null;

    public function setUp()
    {
        parent::setUp();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $job = $this->createJobFactory()->createResolveAndPrepare([
            JobFactory::KEY_TEST_TYPES => ['link integrity'],
        ]);

        $this->queueHttpFixtures(
            $this->buildHttpFixtureSet(
                $this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath($this->getName()). '/HttpResponses')
            )
        );

        $this->tasks = $job->getTasks();

        $task = $this->tasks->first();

        $now = new \DateTime();

        $this->createWorker();

        $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
            'end_date_time' => $now->format('Y-m-d H:i:s'),
            'output' => json_encode($this->taskOutputContent),
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

        $this->executeCommand('simplytestable:task:assigncollection', array(
            'ids' => $this->tasks->get(1)->getId()
        ));
    }

    public function testOnethTaskIsInProgressAfterAssigning()
    {
        $this->assertEquals($this->getTaskService()->getInProgressState(), $this->tasks->get(1)->getState());
    }
}
