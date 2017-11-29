<?php

namespace Tests\ApiBundle\Command;

use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Post\PostBody;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Services\WorkerTaskAssignmentService;
use Tests\ApiBundle\Factory\ConnectExceptionFactory;
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

        $this->workerTaskAssignmentService = $this->container->get(WorkerTaskAssignmentService::class);

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
     * @param array $expectedHttpTransactions
     * @param string[] $expectedTaskStateNames
     */
    public function testAssignCollection(
        $httpFixtures,
        $workerValuesCollection,
        $taskIndicesToAssign,
        $expectedReturnValue,
        $expectedHttpTransactions,
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

        $httpClientService->getHistory()->clear();

        $workers = [];
        foreach ($workerValuesCollection as $workerValues) {
            $workers[] = $this->workerFactory->create($workerValues);
        }

        $returnValue = $this->workerTaskAssignmentService->assignCollection($tasksToAssign, $workers);

        $this->assertEquals($expectedReturnValue, $returnValue);

        $httpHistory = $httpClientService->getHistory();

        $this->assertCount(count($expectedHttpTransactions), $httpHistory);

        foreach ($httpHistory as $httpTransactionIndex => $httpTransaction) {
            $expectedHttpTransaction = $expectedHttpTransactions[$httpTransactionIndex];
            $expectedRequest = $expectedHttpTransaction['request'];
            $expectedResponse = $expectedHttpTransaction['response'];

            /* @var RequestInterface $request */
            $request = $httpTransaction['request'];

            /* @var PostBody $requestBody */
            $requestBody = $request->getBody();

            $requestTasksData = $requestBody->getField('tasks');

            $this->assertEquals($expectedRequest['hostname'], $request->getHost());
            $this->assertEquals($expectedRequest['tasksData'], $requestTasksData);

            /* @var ResponseInterface $response */
            $response = $httpTransaction['response'];

            if (is_null($expectedResponse)) {
                $this->assertNull($response);
            } else {
                $this->assertEquals($expectedResponse['statusCode'], $response->getStatusCode());
            }
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
                'expectedHttpTransactions' => [
                    [
                        'request' => [
                            'hostname' => 'worker.simplytestable.com',
                            'tasksData' => [
                                [
                                    'url' => 'http://example.com/one',
                                    'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                                    'parameters' => '[]',
                                ],
                            ],
                        ],
                        'response' => [
                            'statusCode' => 200,
                        ],
                    ],
                ],
                'expectedTaskStateNames' => [
                    Task::STATE_IN_PROGRESS,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
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
                'expectedHttpTransactions' => [
                    [
                        'request' => [
                            'hostname' => 'worker.simplytestable.com',
                            'tasksData' => [
                                [
                                    'url' => 'http://example.com/one',
                                    'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                                    'parameters' => '[]',
                                ],
                            ],
                        ],
                        'response' => [
                            'statusCode' => 503,
                        ],
                    ],
                ],
                'expectedTaskStateNames' => [
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                ],
            ],
            'single task, single worker, curl failure' => [
                'httpFixtures' => [
                    ConnectExceptionFactory::create('CURL/28 Operation timed out'),
                ],
                'workerValuesCollection' => [
                    [
                        WorkerFactory::KEY_HOSTNAME => 'worker.simplytestable.com',
                    ],
                ],
                'taskIndicesToAssign' => [0],
                'expectedReturnValue' => $couldNotAssignToAnyWorkersReturnCode,
                'expectedHttpTransactions' => [
                    [
                        'request' => [
                            'hostname' => 'worker.simplytestable.com',
                            'tasksData' => [
                                [
                                    'url' => 'http://example.com/one',
                                    'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                                    'parameters' => '[]',
                                ],
                            ],
                        ],
                        'response' => null
                    ],
                ],
                'expectedTaskStateNames' => [
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
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
                'expectedHttpTransactions' => [
                    [
                        'request' => [
                            'hostname' => 'worker1.simplytestable.com',
                            'tasksData' => [
                                [
                                    'url' => 'http://example.com/one',
                                    'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                                    'parameters' => '[]',
                                ],
                            ],
                        ],
                        'response' => [
                            'statusCode' => 503,
                        ],
                    ],
                    [
                        'request' => [
                            'hostname' => 'worker2.simplytestable.com',
                            'tasksData' => [
                                [
                                    'url' => 'http://example.com/one',
                                    'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                                    'parameters' => '[]',
                                ],
                            ],
                        ],
                        'response' => [
                            'statusCode' => 200,
                        ],
                    ],
                ],
                'expectedTaskStateNames' => [
                    Task::STATE_IN_PROGRESS,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
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
                'expectedHttpTransactions' => [
                    [
                        'request' => [
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
                        'response' => [
                            'statusCode' => 200,
                        ],
                    ],
                    [
                        'request' => [
                            'hostname' => 'worker2.simplytestable.com',
                            'tasksData' => [
                                [
                                    'url' => 'http://example.com/one',
                                    'type' => TaskTypeService::CSS_VALIDATION_TYPE,
                                    'parameters' => '[]',
                                ],
                            ],
                        ],
                        'response' => [
                            'statusCode' => 200,
                        ],
                    ],
                    [
                        'request' => [
                            'hostname' => 'worker3.simplytestable.com',
                            'tasksData' => [
                                [
                                    'url' => 'http://example.com/bar%20foo',
                                    'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                                    'parameters' => '[]',
                                ],
                            ],
                        ],
                        'response' => [
                            'statusCode' => 200,
                        ],
                    ],
                ],
                'expectedTaskStateNames' => [
                    Task::STATE_IN_PROGRESS,
                    Task::STATE_IN_PROGRESS,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_IN_PROGRESS,
                    Task::STATE_IN_PROGRESS,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                ],
            ],
        ];
    }
}
