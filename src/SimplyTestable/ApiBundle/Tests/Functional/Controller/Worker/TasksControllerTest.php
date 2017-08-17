<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Worker;

use SimplyTestable\ApiBundle\Controller\MaintenanceController;
use SimplyTestable\ApiBundle\Controller\Worker\TasksController;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Factory\WorkerFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\HttpFoundation\Request;

class TasksControllerTest extends BaseSimplyTestableTestCase
{
    public function testRequestActionInMaintenanceReadOnlyMode()
    {
        $request = new Request();

        $maintenanceController = new MaintenanceController();
        $maintenanceController->setContainer($this->container);
        $maintenanceController->enableReadOnlyAction();

        $tasksController = $this->createTasksController($request);

        $response = $tasksController->requestAction($request);
        $this->assertEquals(503, $response->getStatusCode());

        $maintenanceController->enableBackupReadOnlyAction();

        $response = $tasksController->requestAction($request);
        $this->assertEquals(503, $response->getStatusCode());
    }

    public function testRequest()
    {
        $this->getCrawler([
            'url' => $this->container->get('router')->generate('worker_tasks_request')
        ]);

        $response = $this->getClientResponse();

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @dataProvider requestActionInvalidWorkerHostnameDataProvider
     *
     * @param string[] $workerHostnames
     * @param string $workerHostname
     */
    public function testRequestActionInvalidWorkerHostname($workerHostnames, $workerHostname)
    {
        $workerFactory = new WorkerFactory($this->container);

        foreach ($workerHostnames as $hostname) {
            $workerFactory->create([
                WorkerFactory::KEY_HOSTNAME => $hostname,
            ]);
        }

        $request = new Request(
            [],
            [
                'worker_hostname' => $workerHostname,
            ]
        );
        $tasksController = $this->createTasksController($request);

        $response = $tasksController->requestAction($request);

        $this->assertTrue($response->isClientError());
        $this->assertTrue($response->headers->has('x-message'));
        $this->assertEquals(
            'Invalid hostname "' . $workerHostname . '"',
            $response->headers->get('x-message')
        );
    }

    /**
     * @return array
     */
    public function requestActionInvalidWorkerHostnameDataProvider()
    {
        return [
            'no workers' => [
                'workerHostnames' => [],
                'workerHostname' => 'foo',
            ],
            'invalid hostname' => [
                'workerHostnames' => [
                    'foo',
                    'bar',
                ],
                'workerHostname' => 'foobar',
            ],
        ];
    }

    /**
     * @dataProvider requestActionWorkerInInvalidStateDataProvider
     *
     * @param string $stateName
     */
    public function testRequestActionWorkerInInvalidState($stateName)
    {
        $workerFactory = new WorkerFactory($this->container);
        $worker = $workerFactory->create([
            WorkerFactory::KEY_STATE => $stateName,
        ]);

        $request = new Request(
            [],
            [
                'worker_hostname' => $worker->getHostname(),
            ]
        );
        $tasksController = $this->createTasksController($request);

        $response = $tasksController->requestAction($request);

        $this->assertTrue($response->isClientError());
        $this->assertTrue($response->headers->has('x-message'));
        $this->assertTrue($response->headers->has('x-retryable'));
        $this->assertEquals(
            'Worker is not active',
            $response->headers->get('x-message')
        );
        $this->assertEquals(
            '1',
            $response->headers->get('x-retryable')
        );
    }

    /**
     * @return array
     */
    public function requestActionWorkerInInvalidStateDataProvider()
    {
        return [
            'deleted' => [
                'stateName' => 'worker-deleted',
            ],
            'offline' => [
                'stateName' => 'worker-offline',
            ],
            'unactivated' => [
                'stateName' => 'worker-unactivated',
            ],
        ];
    }

    public function testRequestActionWorkerInInvalidToken()
    {
        $workerFactory = new WorkerFactory($this->container);
        $worker = $workerFactory->create([
            WorkerFactory::KEY_TOKEN => 'foo',
        ]);

        $request = new Request(
            [],
            [
                'worker_hostname' => $worker->getHostname(),
                'worker_token' => 'foo',
            ]
        );
        $tasksController = $this->createTasksController($request);

        $response = $tasksController->requestAction($request);

        $this->assertTrue($response->isClientError());
        $this->assertTrue($response->headers->has('x-message'));
        $this->assertEquals(
            'Invalid token',
            $response->headers->get('x-message')
        );
    }

    public function testRequestActionZeroLimit()
    {
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');

        $workerFactory = new WorkerFactory($this->container);
        $worker = $workerFactory->create();

        $request = new Request(
            [],
            [
                'worker_hostname' => $worker->getHostname(),
                'worker_token' => $worker->getToken(),
                'limit' => 0,
            ]
        );
        $tasksController = $this->createTasksController($request);

        $response = $tasksController->requestAction($request);

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($resqueQueueService->isEmpty('task-assign-collection'));
    }

    public function testRequestActionNoTasksToAssign()
    {
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');

        $workerFactory = new WorkerFactory($this->container);
        $worker = $workerFactory->create();

        $request = new Request(
            [],
            [
                'worker_hostname' => $worker->getHostname(),
                'worker_token' => $worker->getToken(),
                'limit' => 1,
            ]
        );
        $tasksController = $this->createTasksController($request);

        $response = $tasksController->requestAction($request);

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($resqueQueueService->isEmpty('task-assign-collection'));
    }

    public function testRequestActionTasksAlreadyRequested()
    {
        $jobFactory = new JobFactory($this->container);

        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');
        $resqueJobFactory = $this->container->get('simplytestable.services.resque.jobfactoryservice');

        $workerFactory = new WorkerFactory($this->container);
        $worker = $workerFactory->create();

        $job = $jobFactory->createResolveAndPrepare();
        $task = $job->getTasks()->first();

        $resqueQueueService->enqueue(
            $resqueJobFactory->create(
                'task-assign-collection',
                [
                    'ids' => $task->getId(),
                    'worker' => $worker->getHostname(),
                ]
            )
        );

        $request = new Request(
            [],
            [
                'worker_hostname' => $worker->getHostname(),
                'worker_token' => $worker->getToken(),
                'limit' => 1,
            ]
        );

        $tasksController = $this->createTasksController($request);

        $this->assertEquals(
            1,
            $resqueQueueService->getResque()->getQueue('task-assign-collection')->getSize()
        );

        $response = $tasksController->requestAction($request);

        $this->assertTrue($response->isSuccessful());

        $this->assertTrue($resqueQueueService->contains(
            'task-assign-collection',
            [
                'ids' => $task->getId(),
                'worker' => $worker->getHostname(),
            ]
        ));

        $this->assertEquals(
            1,
            $resqueQueueService->getResque()->getQueue('task-assign-collection')->getSize()
        );
    }

    /**
     * @dataProvider requestActionTasksRequestedDataProvider
     *
     * @param $jobValuesCollection
     * @param $limit
     */
    public function testRequestActionTasksRequested($jobValuesCollection, $limit)
    {
        $userFactory = new UserFactory($this->container);
        $jobFactory = new JobFactory($this->container);
        $taskService = $this->container->get('simplytestable.services.taskservice');

        $users = $userFactory->createPublicAndPrivateUserSet();
        $jobs = [];
        $tasks = [];

        foreach ($jobValuesCollection as $jobValues) {
            $jobValues[JobFactory::KEY_USER] = $users[$jobValues[JobFactory::KEY_USER]];
            $job = $jobFactory->createResolveAndPrepare($jobValues);
            $jobs[] = $job;
            $tasks = array_merge($tasks, $job->getTasks()->toArray());
        }

        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');

        $workerFactory = new WorkerFactory($this->container);
        $worker = $workerFactory->create();

        $request = new Request(
            [],
            [
                'worker_hostname' => $worker->getHostname(),
                'worker_token' => $worker->getToken(),
                'limit' => $limit,
            ]
        );

        foreach ($tasks as $task) {
            $this->assertEquals(TaskService::QUEUED_STATE, $task->getState()->getName());
        }

        $this->assertTrue($resqueQueueService->isEmpty('task-assign-collection'));

        $tasksController = $this->createTasksController($request);

        $response = $tasksController->requestAction($request);

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($resqueQueueService->isEmpty('task-assign-collection'));
        $this->assertTrue($resqueQueueService->contains(
            'task-assign-collection',
            ['worker' => $worker->getHostname(),]
        ));

        $resqueJobs = $resqueQueueService->getResque()->getQueue('task-assign-collection')->getJobs();
        $this->assertCount(1, $resqueJobs);

        $resqueJob = $resqueJobs[0];
        $resqueJobArgs = $resqueJob->args;
        $resqueJobTaskIds = explode(',', $resqueJobArgs['ids']);

        $this->assertEquals($worker->getHostname(), $resqueJobArgs['worker']);
        $this->assertCount($limit, $resqueJobTaskIds);

        foreach ($resqueJobTaskIds as $resqueJobTaskId) {
            $task = $taskService->getById($resqueJobTaskId);
            $this->assertEquals(TaskService::QUEUED_FOR_ASSIGNMENT_STATE, $task->getState()->getName());
        }
    }

    /**
     * @return array
     */
    public function requestActionTasksRequestedDataProvider()
    {
        return [
            'limit: 1' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                    ],
                ],
                'limit' => 1,
            ],
            'limit: 3' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                    ],
                ],
                'limit' => 3,
            ],
        ];
    }

    /**
     * @param Request $request
     *
     * @return TasksController
     */
    private function createTasksController(Request $request)
    {
        $controller = new TasksController();
        $this->container->enterScope('request');
        $this->container->set('request', $request);

        $controller->setContainer($this->container);

        return $controller;
    }
}
