<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\JobPreparation\PrepareFromCrawl;

use SimplyTestable\ApiBundle\Controller\TaskController;
use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\TaskControllerCompleteActionRequestFactory;

class HappyPathTest extends AbstractBaseTestCase
{
    const EXPECTED_TASK_TYPE_COUNT = 4;

    /**
     * @var CrawlJobContainer
     */
    private $crawlJobContainer;

    protected function setUp()
    {
        parent::setUp();

        $userService = $this->container->get('simplytestable.services.userservice');
        $crawlJobContainerService = $this->container->get('simplytestable.services.crawljobcontainerservice');
        $jobPreparationService = $this->container->get('simplytestable.services.jobpreparationservice');

        $this->setUser($userService->getPublicUser());

        $userFactory = new UserFactory($this->container);

        $user = $userFactory->create();

        $jobFactory = new JobFactory($this->container);
        $job = $jobFactory->createResolveAndPrepare([
            JobFactory::KEY_SITE_ROOT_URL => 'http://example.com',
            JobFactory::KEY_USER => $user,
            JobFactory::KEY_TEST_TYPES => [
                'html validation',
                'css validation',
                'js static analysis',
                'link integrity',
            ],
        ], [
            'prepare' => HttpFixtureFactory::createStandardCrawlPrepareResponses(),
        ]);

        $this->crawlJobContainer = $crawlJobContainerService->getForJob($job);
        $urlDiscoveryTask = $this->crawlJobContainer->getCrawlJob()->getTasks()->first();

        $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode([
                'http://example.com/0/',
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

        $jobPreparationService->prepareFromCrawl($this->crawlJobContainer);
    }

    public function testStateIsQueued()
    {
        $this->assertEquals(JobService::QUEUED_STATE, $this->getJob()->getState()->getName());
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
        $taskService = $this->container->get('simplytestable.services.taskservice');

        foreach ($this->getJob()->getTasks() as $task) {
            $this->assertEquals($taskService->getQueuedState(), $task->getState());
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
        $crawlJobContainerService = $this->container->get('simplytestable.services.crawljobcontainerservice');

        $discoveredUrlsCount = count($crawlJobContainerService->getDiscoveredUrls($this->crawlJobContainer));
        return self::EXPECTED_TASK_TYPE_COUNT * $discoveredUrlsCount;
    }
}
