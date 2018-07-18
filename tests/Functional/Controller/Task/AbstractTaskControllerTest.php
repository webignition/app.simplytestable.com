<?php

namespace App\Tests\Functional\Controller\Task;

use App\Controller\TaskController;
use App\Services\CrawlJobContainerService;
use App\Services\JobPreparationService;
use App\Services\JobService;
use App\Services\Request\Factory\Task\CompleteRequestFactory;
use App\Services\StateService;
use App\Services\TaskService;
use Symfony\Component\HttpFoundation\Response;
use App\Tests\Factory\MockFactory;
use App\Services\Resque\QueueService as ResqueQueueService;
use App\Services\TaskOutputJoiner\Factory as TaskOutputJoinerFactory;
use App\Services\TaskPostProcessor\Factory as TaskPostProcessorFactory;
use App\Tests\Functional\Controller\AbstractControllerTest;

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
