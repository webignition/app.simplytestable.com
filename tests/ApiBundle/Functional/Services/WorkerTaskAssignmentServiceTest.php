<?php

namespace Tests\ApiBundle\Command;

use Guzzle\Http\Message\EntityEnclosingRequest;
use Guzzle\Http\Message\Response;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Services\WorkerTaskAssignmentService;
use Tests\ApiBundle\Factory\CurlExceptionFactory;
use Tests\ApiBundle\Factory\HttpFixtureFactory;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\WorkerFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

class WorkerTaskAssignmentServiceTest extends AbstractBaseTestCase
{
    /**
     * @var WorkerTaskAssignmentService
     */
    private $workerTaskAssignmentService;

    /**
     * @var WorkerFactory
     */
    private $workerFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->workerTaskAssignmentService = $this->container->get(
            'simplytestable.services.workertaskassignmentservice'
        );

        $this->workerFactory = new WorkerFactory($this->container);
    }

    public function testAssignCollectionNoWorkers()
    {
        $this->assertEquals(
            WorkerTaskAssignmentService::ASSIGN_COLLECTION_NO_WORKERS_STATUS_CODE,
            $this->workerTaskAssignmentService->assignCollection([], [])
        );
    }

    public function testAssignCollectionNoTasks()
    {
        $workers = [
            $this->workerFactory->create(),
        ];

        $this->assertEquals(
            WorkerTaskAssignmentService::ASSIGN_COLLECTION_OK_STATUS_CODE,
            $this->workerTaskAssignmentService->assignCollection([], $workers)
        );
    }

    /**
     * @dataProvider assignCollectionDataProvider
     *
     * @param array $httpFixtures
     * @param array $workerValuesCollection
     * @param int[] $taskIndicesToAssign
     * @param int|bool $expectedReturnValue
     * @param array $expectedRequests
     * @param array $expectedResponses
     * @param string[] $expectedTaskStateNames
     */
    public function testAssignCollectionFoo(
        $httpFixtures,
        $workerValuesCollection,
        $taskIndicesToAssign,
        $expectedReturnValue,
        $expectedRequests,
        $expectedResponses,
        $expectedTaskStateNames
    ) {
        $httpClientService = $this->container->get(HttpClientService::class);

        $jobFactory = new JobFactory($this->container);
        $job = $jobFactory->createResolveAndPrepare([
            JobFactory::KEY_TEST_TYPES => [
                TaskTypeService::HTML_VALIDATION_TYPE,
                TaskTypeService::CSS_VALIDATION_TYPE,
                TaskTypeService::JS_STATIC_ANALYSIS_TYPE,
                TaskTypeService::LINK_INTEGRITY_TYPE,
            ],
        ]);

        $tasksToAssign = [];
        foreach ($job->getTasks() as $taskIndex => $task) {
            if (in_array($taskIndex, $taskIndicesToAssign)) {
                $tasksToAssign[] = $task;
            }
        }

        $this->queueHttpFixtures($httpFixtures);

        $httpHistory = $httpClientService->getHistoryPlugin();
        $httpHistory->clear();

        $workers = [];
        foreach ($workerValuesCollection as $workerValues) {
            $workers[] = $this->workerFactory->create($workerValues);
        }

        $returnValue = $this->workerTaskAssignmentService->assignCollection($tasksToAssign, $workers);

        $this->assertEquals($expectedReturnValue, $returnValue);

        $httpTransactions = $httpClientService->getHistoryPlugin()->getAll();

        $this->assertCount(count($expectedRequests), $httpTransactions);
        $this->assertCount(count($expectedResponses), $httpTransactions);

        foreach ($httpTransactions as $httpTransactionIndex => $httpTransaction) {
            /* @var EntityEnclosingRequest $request */
            $request = $httpTransaction['request'];
            $requestTasksData = $request->getPostField('tasks');

            $expectedRequestData = $expectedRequests[$httpTransactionIndex];
            $this->assertEquals($expectedRequestData['hostname'], $request->getUrl(true)->getHost());
            $this->assertEquals($expectedRequestData['tasksData'], $requestTasksData);

            /* @var Response $response */
            $response = $httpTransaction['response'];

            $expectedResponseData = $expectedResponses[$httpTransactionIndex];
            $this->assertEquals($expectedResponseData['statusCode'], $response->getStatusCode());
        }

        foreach ($job->getTasks() as $taskIndex => $task) {
            $expectedTaskStateName = $expectedTaskStateNames[$taskIndex];

            $this->assertEquals($expectedTaskStateName, $task->getState()->getName());
        }
    }

    /**
     * @return array
     */
    public function assignCollectionDataProvider()
    {
        $couldNotAssignToAnyWorkersReturnCode =
            WorkerTaskAssignmentService::ASSIGN_COLLECTION_COULD_NOT_ASSIGN_TO_ANY_WORKERS_STATUS_CODE;

        return [
            'single task, single worker, success' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createSuccessResponse('application/json', json_encode([
                        [
                            'id' => 100,
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                            'url' => 'http://example.com/one',
                        ],
                    ])),
                ],
                'workerValuesCollection' => [
                    [
                        WorkerFactory::KEY_HOSTNAME => 'worker.simplytestable.com',
                    ],
                ],
                'taskIndicesToAssign' => [0],
                'expectedReturnValue' => WorkerTaskAssignmentService::ASSIGN_COLLECTION_OK_STATUS_CODE,
                'expectedRequests' => [
                    [
                        'hostname' => 'worker.simplytestable.com',
                        'tasksData' => [
                            [
                                'url' => 'http://example.com/one',
                                'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                                'parameters' => '[]',
                            ],
                        ],
                    ],
                ],
                'expectedResponses' => [
                    [
                        'statusCode' => 200,
                    ],
                ],
                'expectedTaskStateNames' => [
                    TaskService::IN_PROGRESS_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                ],
            ],
            'single task, single worker, http failure' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createServiceUnavailableResponse(),
                ],
                'workerValuesCollection' => [
                    [
                        WorkerFactory::KEY_HOSTNAME => 'worker.simplytestable.com',
                    ],
                ],
                'taskIndicesToAssign' => [0],
                'expectedReturnValue' => $couldNotAssignToAnyWorkersReturnCode,
                'expectedRequests' => [
                    [
                        'hostname' => 'worker.simplytestable.com',
                        'tasksData' => [
                            [
                                'url' => 'http://example.com/one',
                                'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                                'parameters' => '[]',
                            ],
                        ],
                    ],
                ],
                'expectedResponses' => [
                    [
                        'statusCode' => 503,
                    ],
                ],
                'expectedTaskStateNames' => [
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                ],
            ],
            'single task, single worker, curl failure' => [
                'httpFixtures' => [
                    CurlExceptionFactory::create('Operation timed out', 28),
                ],
                'workerValuesCollection' => [
                    [
                        WorkerFactory::KEY_HOSTNAME => 'worker.simplytestable.com',
                    ],
                ],
                'taskIndicesToAssign' => [0],
                'expectedReturnValue' => $couldNotAssignToAnyWorkersReturnCode,
                'expectedRequests' => [],
                'expectedResponses' => [],
                'expectedTaskStateNames' => [
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                ],
            ],
            'single task, two workers, http failure for first, succeeds with second' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createServiceUnavailableResponse(),
                    HttpFixtureFactory::createSuccessResponse('application/json', json_encode([
                        [
                            'id' => 100,
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                            'url' => 'http://example.com/one',
                        ],
                    ])),
                ],
                'workerValuesCollection' => [
                    [
                        WorkerFactory::KEY_HOSTNAME => 'worker1.simplytestable.com',
                    ],
                    [
                        WorkerFactory::KEY_HOSTNAME => 'worker2.simplytestable.com',
                    ],
                ],
                'taskIndicesToAssign' => [0],
                'expectedReturnValue' => WorkerTaskAssignmentService::ASSIGN_COLLECTION_OK_STATUS_CODE,
                'expectedRequests' => [
                    [
                        'hostname' => 'worker1.simplytestable.com',
                        'tasksData' => [
                            [
                                'url' => 'http://example.com/one',
                                'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                                'parameters' => '[]',
                            ],
                        ],
                    ],
                    [
                        'hostname' => 'worker2.simplytestable.com',
                        'tasksData' => [
                            [
                                'url' => 'http://example.com/one',
                                'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                                'parameters' => '[]',
                            ],
                        ],
                    ],
                ],
                'expectedResponses' => [
                    [
                        'statusCode' => 503,
                    ],
                    [
                        'statusCode' => 200,
                    ],
                ],
                'expectedTaskStateNames' => [
                    TaskService::IN_PROGRESS_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                ],
            ],
            'multiple tasks, multiple workers, success' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createSuccessResponse('application/json', json_encode([
                        [
                            'id' => 100,
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                            'url' => 'http://example.com/one',
                        ],
                    ])),
                    HttpFixtureFactory::createSuccessResponse('application/json', json_encode([
                        [
                            'id' => 556,
                            'type' => TaskTypeService::CSS_VALIDATION_TYPE,
                            'url' => 'http://example.com/one',
                        ],
                    ])),
                    HttpFixtureFactory::createSuccessResponse('application/json', json_encode([
                        [
                            'id' => 32,
                            'type' => TaskTypeService::JS_STATIC_ANALYSIS_TYPE,
                            'url' => 'http://example.com/one',
                        ],
                    ])),
                    HttpFixtureFactory::createSuccessResponse('application/json', json_encode([
                        [
                            'id' => 32,
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                            'url' => 'http://example.com/bar%20foo',
                        ],
                    ])),
                ],
                'workerValuesCollection' => [
                    [
                        WorkerFactory::KEY_HOSTNAME => 'worker1.simplytestable.com',
                    ],
                    [
                        WorkerFactory::KEY_HOSTNAME => 'worker2.simplytestable.com',
                    ],
                    [
                        WorkerFactory::KEY_HOSTNAME => 'worker3.simplytestable.com',
                    ],
                ],
                'taskIndicesToAssign' => [0, 1, 4, 5],
                'expectedReturnValue' => WorkerTaskAssignmentService::ASSIGN_COLLECTION_OK_STATUS_CODE,
                'expectedRequests' => [
                    [
                        'hostname' => 'worker1.simplytestable.com',
                        'tasksData' => [
                            [
                                'url' => 'http://example.com/one',
                                'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                                'parameters' => '[]',
                            ],
                            [
                                'url' => 'http://example.com/bar%20foo',
                                'type' => TaskTypeService::CSS_VALIDATION_TYPE,
                                'parameters' => '[]',
                            ],
                        ],
                    ],
                    [
                        'hostname' => 'worker2.simplytestable.com',
                        'tasksData' => [
                            [
                                'url' => 'http://example.com/one',
                                'type' => TaskTypeService::CSS_VALIDATION_TYPE,
                                'parameters' => '[]',
                            ],
                        ],
                    ],
                    [
                        'hostname' => 'worker3.simplytestable.com',
                        'tasksData' => [
                            [
                                'url' => 'http://example.com/bar%20foo',
                                'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                                'parameters' => '[]',
                            ],
                        ],
                    ],
                ],
                'expectedResponses' => [
                    [
                        'statusCode' => 200,
                    ],
                    [
                        'statusCode' => 200,
                    ],
                    [
                        'statusCode' => 200,
                    ],
                ],
                'expectedTaskStateNames' => [
                    TaskService::IN_PROGRESS_STATE,
                    TaskService::IN_PROGRESS_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::IN_PROGRESS_STATE,
                    TaskService::IN_PROGRESS_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                ],
            ],
        ];
    }
}
