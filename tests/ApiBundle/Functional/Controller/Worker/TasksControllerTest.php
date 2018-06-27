<?php

namespace Tests\ApiBundle\Functional\Controller\Worker;

use SimplyTestable\ApiBundle\Controller\Worker\TasksController;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Resque\Job\Task\AssignCollectionJob;
use Tests\ApiBundle\Factory\MockFactory;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\ApiBundle\Services\StateService;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Factory\WorkerFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\HttpFoundation\Request;
use SimplyTestable\ApiBundle\Services\Task\QueueService as TaskQueueService;

/**
 * @group Controller/Worker/TasksController
 */
class TasksControllerTest extends AbstractBaseTestCase
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

        $this->tasksController = $this->container->get(TasksController::class);
    }

    public function testRequestActionGetRequest()
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
        $workerFactory = new WorkerFactory($this->container);
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
        $resqueQueueService = $this->container->get(ResqueQueueService::class);
        $resqueQueueService->getResque()->getQueue('task-assign-collection')->clear();

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

        $response = $this->callRequestAction($request);

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($resqueQueueService->isEmpty('task-assign-collection'));
    }

    public function testRequestActionNoTasksToAssign()
    {
        $resqueQueueService = $this->container->get(ResqueQueueService::class);
        $resqueQueueService->getResque()->getQueue('task-assign-collection')->clear();

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

        $response = $this->callRequestAction($request);

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($resqueQueueService->isEmpty('task-assign-collection'));
    }

    public function testRequestActionTasksAlreadyRequested()
    {
        $jobFactory = new JobFactory($this->container);

        $resqueQueueService = $this->container->get(ResqueQueueService::class);

        $resqueQueueService->getResque()->getQueue('task-assign-collection')->clear();

        $workerFactory = new WorkerFactory($this->container);
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
        $userFactory = new UserFactory($this->container);
        $jobFactory = new JobFactory($this->container);
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $resqueQueueService = $this->container->get(ResqueQueueService::class);

        $resqueQueueService->getResque()->getQueue('task-assign-collection')->clear();

        $taskRepository = $entityManager->getRepository(Task::class);

        $users = $userFactory->createPublicAndPrivateUserSet();
        $jobs = [];
        $tasks = [];

        foreach ($jobValuesCollection as $jobValues) {
            $jobValues[JobFactory::KEY_USER] = $users[$jobValues[JobFactory::KEY_USER]];
            $job = $jobFactory->createResolveAndPrepare($jobValues);
            $jobs[] = $job;
            $tasks = array_merge($tasks, $job->getTasks()->toArray());
        }

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
            $this->container->get('doctrine.orm.entity_manager'),
            $this->container->get(ResqueQueueService::class),
            $this->container->get(StateService::class),
            $this->container->get(TaskQueueService::class),
            $request
        );
    }
}
