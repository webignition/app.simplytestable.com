<?php

namespace Tests\ApiBundle\Functional\Services\Resque;

use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use SimplyTestable\ApiBundle\Services\Worker\TaskNotificationService;
use Tests\ApiBundle\Factory\ConnectExceptionFactory;
use Tests\ApiBundle\Factory\HttpFixtureFactory;
use Tests\ApiBundle\Factory\WorkerFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

class TaskNotificationServiceTest extends AbstractBaseTestCase
{
    /**
     * @var TaskNotificationService
     */
    private $taskNotificationService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->taskNotificationService = $this->container->get(TaskNotificationService::class);
    }

    /**
     * @dataProvider notifyDataProvider
     *
     * @param array $httpFixtures
     * @param array $workerValuesCollection
     * @param array $expectedHttpTransactions
     */
    public function testNotify($httpFixtures, $workerValuesCollection, $expectedHttpTransactions)
    {
        $this->queueHttpFixtures($httpFixtures);

        $workerFactory = new WorkerFactory($this->container);
        foreach ($workerValuesCollection as $workerValues) {
            $workerFactory->create($workerValues);
        }

        $this->taskNotificationService->notify();

        $httpClientService = $this->container->get(HttpClientService::class);
        $httpClientService->get();

        $httpHistory = $httpClientService->getHistory();

        $this->assertCount(count($expectedHttpTransactions), $httpHistory);

        foreach ($httpHistory as $httpTransactionIndex => $httpTransaction) {
            $expectedHttpTransaction = $expectedHttpTransactions[$httpTransactionIndex];
            $expectedRequest = $expectedHttpTransaction['request'];
            $expectedResponse = $expectedHttpTransaction['response'];

            /* @var RequestInterface $request */
            $request = $httpTransaction['request'];

            $this->assertEquals($expectedRequest['hostname'], $request->getHost());

            /* @var ResponseInterface $response */
            $response = $httpTransaction['response'];

            if (is_null($expectedResponse)) {
                $this->assertNull($response);
            } else {
                $this->assertEquals($expectedResponse['statusCode'], $response->getStatusCode());
            }
        }
    }

    /**
     * @return array
     */
    public function notifyDataProvider()
    {
        return [
            'no workers' => [
                'httpFixtures' => [],
                'workerValuesCollection' => [],
                'expectedRequests' => [],
                'expectedResponses' => [],
            ],
            'no active workers' => [
                'httpFixtures' => [],
                'workerValuesCollection' => [
                    [
                        WorkerFactory::KEY_STATE => Worker::STATE_UNACTIVATED,
                    ],
                ],
                'expectedHttpTransactions' => [],
            ],
            'single active worker, client error response' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createBadRequestResponse(),
                ],
                'workerValuesCollection' => [
                    [
                        WorkerFactory::KEY_HOSTNAME => 'worker.simplytestable.com',
                        WorkerFactory::KEY_STATE => Worker::STATE_ACTIVE,
                    ],
                ],
                'expectedHttpTransactions' => [
                    [
                        'request' => [
                            'hostname' => 'worker.simplytestable.com',
                        ],
                        'response' => [
                            'statusCode' => 400,
                        ],
                    ],
                ],
            ],
            'single active worker, server error response' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createServiceUnavailableResponse(),
                ],
                'workerValuesCollection' => [
                    [
                        WorkerFactory::KEY_HOSTNAME => 'worker.simplytestable.com',
                        WorkerFactory::KEY_STATE => Worker::STATE_ACTIVE,
                    ],
                ],
                'expectedHttpTransactions' => [
                    [
                        'request' => [
                            'hostname' => 'worker.simplytestable.com',
                        ],
                        'response' => [
                            'statusCode' => 503,
                        ],
                    ],
                ],
            ],
            'single active worker, curl error response' => [
                'httpFixtures' => [
                    ConnectExceptionFactory::create('CURL/28 Operation timed out'),
                ],
                'workerValuesCollection' => [
                    [
                        WorkerFactory::KEY_HOSTNAME => 'worker.simplytestable.com',
                        WorkerFactory::KEY_STATE => Worker::STATE_ACTIVE,
                    ],
                ],
                'expectedHttpTransactions' => [
                    [
                        'request' => [
                            'hostname' => 'worker.simplytestable.com',
                        ],
                        'response' => null,
                    ],
                ],
            ],
            'many workers, mixed responses' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createSuccessResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'workerValuesCollection' => [
                    [
                        WorkerFactory::KEY_HOSTNAME => 'worker1.simplytestable.com',
                        WorkerFactory::KEY_STATE => Worker::STATE_ACTIVE,
                    ],
                    [
                        WorkerFactory::KEY_HOSTNAME => 'worker2.simplytestable.com',
                        WorkerFactory::KEY_STATE => Worker::STATE_ACTIVE,
                    ],
                    [
                        WorkerFactory::KEY_HOSTNAME => 'worker3.simplytestable.com',
                        WorkerFactory::KEY_STATE => Worker::STATE_ACTIVE,
                    ],
                ],
                'expectedHttpTransactions' => [
                    [
                        'request' => [
                            'hostname' => 'worker1.simplytestable.com',
                        ],
                        'response' => [
                            'statusCode' => 200,
                        ],
                    ],
                    [
                        'request' => [
                            'hostname' => 'worker2.simplytestable.com',
                        ],
                        'response' => [
                            'statusCode' => 404,
                        ],
                    ],
                    [
                        'request' => [
                            'hostname' => 'worker3.simplytestable.com',
                        ],
                        'response' => [
                            'statusCode' => 200,
                        ],
                    ],
                ],
            ],
        ];
    }
}
