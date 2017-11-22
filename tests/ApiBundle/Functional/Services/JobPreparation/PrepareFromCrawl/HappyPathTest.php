<?php

namespace Tests\ApiBundle\Functional\Services\JobPreparation\PrepareFromCrawl;

use SimplyTestable\ApiBundle\Controller\TaskController;
use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;
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

        $userService = $this->container->get(UserService::class);
        $crawlJobContainerService = $this->container->get(CrawlJobContainerService::class);
        $jobPreparationService = $this->container->get(JobPreparationService::class);

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

        $taskController = $this->container->get(TaskController::class);

        $this->container->get('request_stack')->push($taskCompleteRequest);
        $this->container->get(CompleteRequestFactory::class)->init($taskCompleteRequest);

        $this->callTaskControllerCompleteAction($taskController);

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
        $this->assertEquals(8, $this->getJob()->getTasks()->count());
    }

    public function testTaskStates()
    {
        foreach ($this->getJob()->getTasks() as $task) {
            $this->assertEquals(TaskService::QUEUED_STATE, $task->getState()->getName());
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
