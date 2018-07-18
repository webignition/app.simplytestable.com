<?php

namespace Tests\AppBundle\Unit\Controller\Task;

use Mockery\Mock;
use AppBundle\Controller\TaskController;
use AppBundle\Request\Task\CompleteRequest;
use AppBundle\Services\ApplicationStateService;
use AppBundle\Services\CrawlJobContainerService;
use AppBundle\Services\JobPreparationService;
use AppBundle\Services\JobService;
use AppBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use AppBundle\Services\StateService;
use AppBundle\Services\TaskService;
use AppBundle\Services\TaskTypeDomainsToIgnoreService;
use Symfony\Component\HttpFoundation\Response;
use Tests\AppBundle\Factory\MockFactory;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use AppBundle\Services\Resque\QueueService as ResqueQueueService;
use AppBundle\Services\TaskOutputJoiner\Factory as TaskOutputJoinerFactory;
use AppBundle\Services\TaskPostProcessor\Factory as TaskPostProcessorFactory;

/**
 * @group Controller/TaskController
 */
class TaskControllerCompleteActionTest extends AbstractTaskControllerTest
{
    public function testCompleteActionInReadOnlyMode()
    {
        $taskController = $this->createTaskController();

        $this->expectException(ServiceUnavailableHttpException::class);

        $this->callCompleteAction($taskController, [
            ApplicationStateService::class => MockFactory::createApplicationStateService(true),
        ]);
    }

    public function testCompleteActionInvalidRequest()
    {
        /* @var Mock|CompleteRequest $completeRequest */
        $completeRequest = \Mockery::mock(CompleteRequest::class);
        $completeRequest
            ->shouldReceive('isValid')
            ->andReturn(false);

        $completeRequestFactory = MockFactory::createCompleteRequestFactory([
            'create' => [
                'return' => $completeRequest,
            ],
        ]);

        $this->expectException(BadRequestHttpException::class);

        $taskController = $this->createTaskController();

        $this->callCompleteAction($taskController, [
            CompleteRequestFactory::class => $completeRequestFactory,
        ]);
    }

    public function testCompleteActionNoMatchingTasks()
    {
        /* @var Mock|CompleteRequest $completeRequest */
        $completeRequest = \Mockery::mock(CompleteRequest::class);
        $completeRequest
            ->shouldReceive('isValid')
            ->andReturn(true);

        $completeRequest
            ->shouldReceive('getTasks')
            ->andReturn([]);

        $completeRequestFactory = MockFactory::createCompleteRequestFactory([
            'create' => [
                'return' => $completeRequest,
            ],
        ]);

        $this->expectException(GoneHttpException::class);

        $taskController = $this->createTaskController();

        $this->callCompleteAction($taskController, [
            CompleteRequestFactory::class => $completeRequestFactory,
        ]);
    }

    /**
     * @param TaskController $taskController
     * @param array $services
     *
     * @return Response
     */
    private function callCompleteAction(TaskController $taskController, $services)
    {
        if (!isset($services[ApplicationStateService::class])) {
            $services[ApplicationStateService::class] = MockFactory::createApplicationStateService();
        }

        if (!isset($services[ResqueQueueService::class])) {
            $services[ResqueQueueService::class] = MockFactory::createResqueQueueService();
        }

        if (!isset($services[CompleteRequestFactory::class])) {
            $services[CompleteRequestFactory::class] = MockFactory::createCompleteRequestFactory();
        }

        if (!isset($services[TaskService::class])) {
            $services[TaskService::class] = MockFactory::createTaskService();
        }

        if (!isset($services[JobService::class])) {
            $services[JobService::class] = MockFactory::createJobService();
        }

        if (!isset($services[JobPreparationService::class])) {
            $services[JobPreparationService::class] = MockFactory::createJobPreparationService();
        }

        if (!isset($services[CrawlJobContainerService::class])) {
            $services[CrawlJobContainerService::class] = MockFactory::createCrawlJobContainerService();
        }

        if (!isset($services[TaskOutputJoinerFactory::class])) {
            $services[TaskOutputJoinerFactory::class] = MockFactory::createTaskOutputJoinerFactory();
        }

        if (!isset($services[TaskPostProcessorFactory::class])) {
            $services[TaskPostProcessorFactory::class] = MockFactory::createTaskPostProcessorFactory();
        }

        if (!isset($services[StateService::class])) {
            $services[StateService::class] = MockFactory::createStateService();
        }

        if (!isset($services[TaskTypeDomainsToIgnoreService::class])) {
            $services[TaskTypeDomainsToIgnoreService::class] = MockFactory::createTaskTypeDomainsToIgnoreService();
        }

        return $taskController->completeAction(
            $services[ApplicationStateService::class],
            $services[ResqueQueueService::class],
            $services[CompleteRequestFactory::class],
            $services[TaskService::class],
            $services[JobService::class],
            $services[JobPreparationService::class],
            $services[CrawlJobContainerService::class],
            $services[TaskOutputJoinerFactory::class],
            $services[TaskPostProcessorFactory::class],
            $services[StateService::class],
            $services[TaskTypeDomainsToIgnoreService::class]
        );
    }
}
