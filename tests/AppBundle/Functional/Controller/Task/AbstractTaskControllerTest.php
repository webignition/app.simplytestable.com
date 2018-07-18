<?php

namespace Tests\AppBundle\Functional\Controller\Task;

use AppBundle\Controller\TaskController;
use AppBundle\Services\CrawlJobContainerService;
use AppBundle\Services\JobPreparationService;
use AppBundle\Services\JobService;
use AppBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use AppBundle\Services\StateService;
use AppBundle\Services\TaskService;
use Symfony\Component\HttpFoundation\Response;
use Tests\AppBundle\Factory\MockFactory;
use AppBundle\Services\Resque\QueueService as ResqueQueueService;
use AppBundle\Services\TaskOutputJoiner\Factory as TaskOutputJoinerFactory;
use AppBundle\Services\TaskPostProcessor\Factory as TaskPostProcessorFactory;
use Tests\AppBundle\Functional\Controller\AbstractControllerTest;

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
