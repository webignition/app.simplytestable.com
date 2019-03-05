<?php

namespace App\Tests\Functional\Controller\Worker;

use App\Controller\Worker\TasksController;
use App\Entity\Task\Task;
use App\Repository\TaskRepository;
use App\Resque\Job\Task\AssignCollectionJob;
use App\Tests\Factory\MockFactory;
use App\Services\Resque\QueueService as ResqueQueueService;
use App\Services\StateService;
use App\Tests\Factory\JobFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Factory\WorkerFactory;
use Symfony\Component\HttpFoundation\Request;
use App\Services\Task\QueueService as TaskQueueService;
use App\Tests\Functional\Controller\AbstractControllerTest;

/**
 * @group Controller/Worker/TasksController
 */
class TasksControllerTest extends AbstractControllerTest
{
    /**
     * @var TasksController
     */
    private $tasksController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->tasksController = self::$container->get(TasksController::class);
    }

    public function testRequestActionGetRequest()
    {
        $this->getCrawler([
            'url' => self::$container->get('router')->generate('worker_tasks_request')
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
        $workerFactory = new WorkerFactory(self::$container);

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

        $response = $this->callRequestAction($request);

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
        $workerFactory = new WorkerFactory(self::$container);
        $worker = $workerFactory->create([
            WorkerFactory::KEY_STATE => $stateName,
        ]);

        $request = new Request(
            [],
            [
                'worker_hostname' => $worker->getHostname(),
            ]
        );

        $response = $this->callRequestAction($request);

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
        $workerFactory = new WorkerFactory(self::$container);
        $worker = $workerFactory->create([
            WorkerFactory::KEY_TOKEN => 'foo',
        ]);

        $request = new Request(
            [],
            [
                'worker_hostname' => $worker->getHostname(),
                'worker_token' => 'bar',
            ]
        );

        $response = $this->callRequestAction($request);

        $this->assertTrue($response->isClientError());
        $this->assertTrue($response->headers->has('x-message'));
        $this->assertEquals(
            'Invalid token',
            $response->headers->get('x-message')
        );
    }

    public function testRequestActionZeroLimit()
    {
        $resqueQueueService = self::$container->get(ResqueQueueService::class);
        $resqueQueueService->getResque()->getQueue('task-assign-collection')->clear();

        $workerFactory = new WorkerFactory(self::$container);
        $worker = $workerFactory->create();

        $request = new Request(
            [],
            [
                'worker_hostname' => $worker->getHostname(),
                'worker_token' => $worker->getToken(),
                'limit' => 0,
            ]
        );

        $response = $this->callRequestAction($request);

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($resqueQueueService->isEmpty('task-assign-collection'));
    }

    public function testRequestActionNoTasksToAssign()
    {
        $resqueQueueService = self::$container->get(ResqueQueueService::class);
        $resqueQueueService->getResque()->getQueue('task-assign-collection')->clear();

        $workerFactory = new WorkerFactory(self::$container);
        $worker = $workerFactory->create();

        $request = new Request(
            [],
            [
                'worker_hostname' => $worker->getHostname(),
                'worker_token' => $worker->getToken(),
                'limit' => 1,
            ]
        );

        $response = $this->callRequestAction($request);

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($resqueQueueService->isEmpty('task-assign-collection'));
    }

    public function testRequestActionTasksAlreadyRequested()
    {
        $jobFactory = new JobFactory(self::$container);

        $resqueQueueService = self::$container->get(ResqueQueueService::class);

        $resqueQueueService->getResque()->getQueue('task-assign-collection')->clear();

        $workerFactory = new WorkerFactory(self::$container);
        $worker = $workerFactory->create();

        $job = $jobFactory->createResolveAndPrepare();
        $task = $job->getTasks()->first();

        $resqueQueueService->enqueue(new AssignCollectionJob([
            'ids' => $task->getId(),
            'worker' => $worker->getHostname(),
        ]));

        $request = new Request(
            [],
            [
                'worker_hostname' => $worker->getHostname(),
                'worker_token' => $worker->getToken(),
                'limit' => 1,
            ]
        );

        $this->assertEquals(
            1,
            $resqueQueueService->getResque()->getQueue('task-assign-collection')->getSize()
        );

        $response = $this->callRequestAction($request);

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
        $userFactory = new UserFactory(self::$container);
        $jobFactory = new JobFactory(self::$container);
        $resqueQueueService = self::$container->get(ResqueQueueService::class);
        $taskRepository = self::$container->get(TaskRepository::class);

        $resqueQueueService->getResque()->getQueue('task-assign-collection')->clear();

        $users = $userFactory->createPublicAndPrivateUserSet();
        $jobs = [];
        $tasks = [];

        foreach ($jobValuesCollection as $jobValues) {
            $jobValues[JobFactory::KEY_USER] = $users[$jobValues[JobFactory::KEY_USER]];
            $job = $jobFactory->createResolveAndPrepare($jobValues);
            $jobs[] = $job;
            $tasks = array_merge($tasks, $job->getTasks()->toArray());
        }

        $workerFactory = new WorkerFactory(self::$container);
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
            $this->assertEquals(Task::STATE_QUEUED, $task->getState()->getName());
        }

        $this->assertTrue($resqueQueueService->isEmpty('task-assign-collection'));

        $response = $this->callRequestAction($request);

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
            $task = $taskRepository->find($resqueJobTaskId);
            $this->assertEquals(Task::STATE_QUEUED_FOR_ASSIGNMENT, $task->getState()->getName());
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

    private function callRequestAction(Request $request)
    {
        return $this->tasksController->requestAction(
            MockFactory::createApplicationStateService(),
            self::$container->get('doctrine.orm.entity_manager'),
            self::$container->get(ResqueQueueService::class),
            self::$container->get(StateService::class),
            self::$container->get(TaskQueueService::class),
            self::$container->get(TaskRepository::class),
            $request
        );
    }
}
