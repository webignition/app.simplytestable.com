<?php

namespace Tests\ApiBundle\Functional\Services\JobPreparation\PrepareFromCrawl\NoDiscoveredUrls;

use SimplyTestable\ApiBundle\Controller\TaskController;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\CrawlJobContainerService;
use SimplyTestable\ApiBundle\Services\JobPreparationService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeDomainsToIgnoreService;
use SimplyTestable\ApiBundle\Services\UserService;
use Symfony\Component\HttpFoundation\Response;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Tests\ApiBundle\Factory\HttpFixtureFactory;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\TaskControllerCompleteActionRequestFactory;
use SimplyTestable\ApiBundle\Services\Resque\JobFactory as ResqueJobFactory;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\ApiBundle\Services\TaskOutputJoiner\Factory as TaskOutputJoinerFactory;
use SimplyTestable\ApiBundle\Services\TaskPostProcessor\Factory as TaskPostProcessorFactory;

class ServiceTest extends AbstractBaseTestCase
{
    const EXPECTED_TASK_TYPE_COUNT = 4;

    /**
     * @var Job
     */
    private $job;

    protected function setUp()
    {
        parent::setUp();

        $userService = $this->container->get(UserService::class);
        $this->setUser($userService->getPublicUser());

        $userFactory = new UserFactory($this->container);

        $jobFactory = new JobFactory($this->container);
        $this->job = $jobFactory->createResolveAndPrepare([
            JobFactory::KEY_USER => $userFactory->create(),
            JobFactory::KEY_TEST_TYPES => [
                'html validation',
                'css validation',
                'js static analysis',
                'link integrity',
            ],
        ], [
            'prepare' => HttpFixtureFactory::createStandardCrawlPrepareResponses(),
        ]);

        $crawlJobContainerService = $this->container->get(CrawlJobContainerService::class);

        $crawlJobContainer = $crawlJobContainerService->getForJob($this->job);
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

        $taskController = $this->container->get(TaskController::class);

        $this->container->get('request_stack')->push($taskCompleteRequest);
        $this->container->get(CompleteRequestFactory::class)->init($taskCompleteRequest);

        $this->callTaskControllerCompleteAction($taskController);
    }

    public function testStateIsQueued()
    {
        $this->assertEquals(JobService::QUEUED_STATE, $this->job->getState()->getName());
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
            $this->assertEquals(TaskService::QUEUED_STATE, $task->getState()->getName());
        }
    }

    /**
     * @param TaskController $taskController
     *
     * @return Response
     */
    private function callTaskControllerCompleteAction(TaskController $taskController)
    {
        return $taskController->completeAction(
            $this->container->get(ApplicationStateService::class),
            $this->container->get(ResqueQueueService::class),
            $this->container->get(ResqueJobFactory::class),
            $this->container->get(CompleteRequestFactory::class),
            $this->container->get(TaskService::class),
            $this->container->get(JobService::class),
            $this->container->get(JobPreparationService::class),
            $this->container->get(CrawlJobContainerService::class),
            $this->container->get(TaskOutputJoinerFactory::class),
            $this->container->get(TaskPostProcessorFactory::class),
            $this->container->get(StateService::class),
            $this->container->get(TaskTypeDomainsToIgnoreService::class)
        );
    }
}
