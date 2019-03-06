<?php

namespace App\Tests\Functional\Services;

use App\Tests\Services\JobFactory;
use App\Tests\Services\TestHttpClientService;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use App\Entity\Task\Task;
use App\Services\HttpClientService;
use App\Services\TaskTypeService;
use App\Services\WorkerTaskAssignmentService;
use App\Tests\Factory\ConnectExceptionFactory;
use App\Tests\Factory\WorkerFactory;
use App\Tests\Functional\AbstractBaseTestCase;

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

        $this->workerTaskAssignmentService = self::$container->get(WorkerTaskAssignmentService::class);

        $this->workerFactory = new WorkerFactory(self::$container);
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
     * @dataProvider assignCollectionSuccessDataProvider
     *
     * @param array $httpFixtures
     * @param array $workerValuesCollection
     * @param int[] $taskIndicesToAssign
     * @param int|bool $expectedReturnValue
     * @param array $expectedHttpTransactions
     * @param string[] $expectedTaskStateNames
     */
    public function testAssignCollectionSuccess(
        $httpFixtures,
        $workerValuesCollection,
        $taskIndicesToAssign,
        $expectedReturnValue,
        $expectedHttpTransactions,
        $expectedTaskStateNames
    ) {
        /* @var TestHttpClientService $httpClientService */
        $httpClientService = self::$container->get(HttpClientService::class);

        $jobFactory = self::$container->get(JobFactory::class);
        $job = $jobFactory->createResolveAndPrepare([
            JobFactory::KEY_TEST_TYPES => [
                TaskTypeService::HTML_VALIDATION_TYPE,
                TaskTypeService::CSS_VALIDATION_TYPE,
                TaskTypeService::LINK_INTEGRITY_TYPE,
            ],
        ]);

        $tasksToAssign = [];
        foreach ($job->getTasks() as $taskIndex => $task) {
            if (in_array($taskIndex, $taskIndicesToAssign)) {
                $tasksToAssign[] = $task;
            }
        }

        $httpClientService->getHistory()->clear();
        $httpClientService->appendFixtures($httpFixtures);

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

            $postedData = [];
            parse_str($request->getBody()->getContents(), $postedData);

            $this->assertEquals($expectedRequest['hostname'], $request->getUri()->getHost());
            $this->assertEquals($expectedRequest['tasksData'], $postedData['tasks']);

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
    public function assignCollectionSuccessDataProvider()
    {
        $couldNotAssignToAnyWorkersReturnCode =
            WorkerTaskAssignmentService::ASSIGN_COLLECTION_COULD_NOT_ASSIGN_TO_ANY_WORKERS_STATUS_CODE;

        $serviceUnavailableResponse = new Response(503);
        $curl28ConnectException = ConnectExceptionFactory::create(28, 'Operation timed out');

        return [
            'single task, single worker, success' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'application/json'], json_encode([
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
                    $serviceUnavailableResponse,
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
                    $curl28ConnectException,
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
                    $serviceUnavailableResponse,
                    new Response(200, ['content-type' => 'application/json'], json_encode([
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
                    new Response(200, ['content-type' => 'application/json'], json_encode([
                        [
                            'id' => 100,
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                            'url' => 'http://example.com/one',
                        ],
                    ])),
                    new Response(200, ['content-type' => 'application/json'], json_encode([
                        [
                            'id' => 556,
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                            'url' => 'http://example.com/one',
                        ],
                    ])),
                    new Response(200, ['content-type' => 'application/json'], json_encode([
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
                                ],
                                [
                                    'url' => 'http://example.com/bar%20foo',
                                    'type' => TaskTypeService::LINK_INTEGRITY_TYPE,
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
                                    'type' => TaskTypeService::CSS_VALIDATION_TYPE  ,
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
