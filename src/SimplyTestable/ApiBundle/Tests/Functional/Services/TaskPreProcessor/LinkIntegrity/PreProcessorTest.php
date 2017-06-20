<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\TaskPreProcessor\LinkIntegrity;

use Doctrine\ORM\PersistentCollection;
use SimplyTestable\ApiBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\TaskControllerCompleteActionRequestFactory;

abstract class PreProcessorTest extends BaseSimplyTestableTestCase
{
    /**
     * @var PersistentCollection
     */
    protected $tasks = null;

    /**
     * @return array
     */
    abstract protected function getCompletedTaskOutput();

    /**
     * @return array
     */
    protected function getTestTypeOptions()
    {
        return array();
    }

    /**
     * @return array
     */
    protected function getJobParameters()
    {
        return array();
    }

    public function setUp()
    {
        parent::setUp();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $job = $this->createJobFactory()->createResolveAndPrepare([
            JobFactory::KEY_TEST_TYPES => ['link integrity'],
            JobFactory::KEY_TEST_TYPE_OPTIONS => $this->getTestTypeOptions(),
            JobFactory::KEY_PARAMETERS => $this->getJobParameters(),
        ]);

        $this->queueHttpFixtures(
            $this->buildHttpFixtureSet(
                $this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath($this->getName()). '/HttpResponses')
            )
        );

        $this->tasks = $job->getTasks();

        $now = new \DateTime();

        $task = $this->tasks->first();

        $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
            'end_date_time' => $now->format('Y-m-d H:i:s'),
            'output' => json_encode($this->getCompletedTaskOutput()),
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
    }

    protected function getDefaultCompletedTaskOutput()
    {
        return array(
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
    }
}
