<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\JobPreparation\PrepareFromCrawl;

use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\TaskControllerCompleteActionRequestFactory;

class HappyPathTest extends BaseSimplyTestableTestCase
{
    const EXPECTED_TASK_TYPE_COUNT = 4;

    /**
     * @var CrawlJobContainer
     */
    private $crawlJobContainer;

    public function setUp()
    {
        parent::setUp();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $job = $this->createJobFactory()->createResolveAndPrepare([
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

        $this->crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $urlDiscoveryTask = $this->crawlJobContainer->getCrawlJob()->getTasks()->first();

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

        $taskController = $this->createControllerFactory()->createTaskController($taskCompleteRequest);
        $taskController->completeAction();

        $this->getJobPreparationService()->prepareFromCrawl($this->crawlJobContainer);
    }

    public function testStateIsQueued()
    {
        $this->assertEquals($this->getJobService()->getQueuedState(), $this->getJob()->getState());
    }

    public function testHasStartTime()
    {
        $this->assertNotNull($this->getJob()->getTimePeriod());
        $this->assertNotNull($this->getJob()->getTimePeriod()->getStartDateTime());
    }

    public function testHasNotEndTime()
    {
        $this->assertNull($this->getJob()->getTimePeriod()->getEndDateTime());
    }

    public function testHasTasks()
    {
        $this->assertEquals($this->getExpectedTaskCount(), $this->getJob()->getTasks()->count());
    }

    public function testTaskStates()
    {
        foreach ($this->getJob()->getTasks() as $task) {
            $this->assertEquals($this->getTaskService()->getQueuedState(), $task->getState());
        }
    }

    /**
     * @return Job
     */
    private function getJob()
    {
        return $this->crawlJobContainer->getParentJob();
    }

    /**
     * @return int
     */
    private function getExpectedTaskCount()
    {
        $discoveredUrlsCount = count($this->getCrawlJobContainerService()->getDiscoveredUrls($this->crawlJobContainer));
        return self::EXPECTED_TASK_TYPE_COUNT * $discoveredUrlsCount;
    }
}