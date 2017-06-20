<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\PrepareFromCrawl\NoDiscoveredUrls;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\TaskControllerCompleteActionRequestFactory;

class ServiceTest extends BaseSimplyTestableTestCase
{
    const EXPECTED_TASK_TYPE_COUNT = 4;

    /**
     * @var Job
     */
    private $job;

    public function setUp()
    {
        parent::setUp();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $this->job = $this->createJobFactory()->createResolveAndPrepare([
            JobFactory::KEY_USER => $this->getTestUser(),
            JobFactory::KEY_TEST_TYPES => [
                'html validation',
                'css validation',
                'js static analysis',
                'link integrity',
            ],
        ], [
            'prepare' => HttpFixtureFactory::createStandardCrawlPrepareResponses(),
        ]);

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($this->job);
        $urlDiscoveryTask = $crawlJobContainer->getCrawlJob()->getTasks()->first();

        $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '[]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ], [
            CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $urlDiscoveryTask->getType(),
            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => $urlDiscoveryTask->getUrl(),
            CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $urlDiscoveryTask->getParametersHash(),
        ]);

        $taskController = $this->createControllerFactory()->createTaskController($taskCompleteRequest);
        $taskController->completeAction();
    }

    public function testStateIsQueued()
    {
        $this->assertEquals($this->getJobService()->getQueuedState(), $this->job->getState());
    }

    public function testHasStartTime()
    {
        $this->assertNotNull($this->job->getTimePeriod());
        $this->assertNotNull($this->job->getTimePeriod()->getStartDateTime());
    }

    public function testHasNotEndTime()
    {
        $this->assertNull($this->job->getTimePeriod()->getEndDateTime());
    }

    public function testHasTasks()
    {
        $this->assertEquals(self::EXPECTED_TASK_TYPE_COUNT, $this->job->getTasks()->count());
    }

    public function testTaskUrls()
    {
        foreach ($this->job->getTasks() as $task) {
            $this->assertTrue(in_array($task->getUrl(), array(
                'http://example.com/'
            )));
        }
    }

    public function testTaskStates()
    {
        foreach ($this->job->getTasks() as $task) {
            $this->assertEquals($this->getTaskService()->getQueuedState(), $task->getState());
        }
    }
}
