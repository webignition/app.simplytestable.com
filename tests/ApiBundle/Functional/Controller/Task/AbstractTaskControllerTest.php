<?php

namespace Tests\ApiBundle\Functional\Controller\Task;

use SimplyTestable\ApiBundle\Controller\TaskController;
use SimplyTestable\ApiBundle\Services\CrawlJobContainerService;
use SimplyTestable\ApiBundle\Services\JobPreparationService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\TaskService;
use Symfony\Component\HttpFoundation\Response;
use Tests\ApiBundle\Factory\MockFactory;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\ApiBundle\Services\TaskOutputJoiner\Factory as TaskOutputJoinerFactory;
use SimplyTestable\ApiBundle\Services\TaskPostProcessor\Factory as TaskPostProcessorFactory;
use Tests\ApiBundle\Functional\Controller\AbstractControllerTest;

abstract class AbstractTaskControllerTest extends AbstractControllerTest
{
    /**
     * @var TaskController
     */
    protected $taskController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->taskController = self::$container->get(TaskController::class);
    }

    /**
     * @return Response
     */
    protected function callCompleteAction()
    {
        $taskTypeDomainsToIgnoreService = MockFactory::createTaskTypeDomainsToIgnoreService();
        $taskTypeDomainsToIgnoreService
            ->shouldReceive('getForTaskType')
            ->andReturn([]);

        return $this->taskController->completeAction(
            MockFactory::createApplicationStateService(),
            self::$container->get(ResqueQueueService::class),
            self::$container->get(CompleteRequestFactory::class),
            self::$container->get(TaskService::class),
            self::$container->get(JobService::class),
            self::$container->get(JobPreparationService::class),
            self::$container->get(CrawlJobContainerService::class),
            self::$container->get(TaskOutputJoinerFactory::class),
            self::$container->get(TaskPostProcessorFactory::class),
            self::$container->get(StateService::class),
            $taskTypeDomainsToIgnoreService
        );
    }
}
