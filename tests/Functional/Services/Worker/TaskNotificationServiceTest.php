<?php

namespace App\Tests\Functional\Services\Worker;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use App\Entity\Worker;
use App\Services\HttpClientService;
use App\Services\Worker\TaskNotificationService;
use App\Tests\Factory\ConnectExceptionFactory;
use App\Tests\Services\WorkerFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\TestHttpClientService;

class TaskNotificationServiceTest extends AbstractBaseTestCase
{
    /**
     * @var TaskNotificationService
     */
    private $taskNotificationService;

    /**
     * @var TestHttpClientService
     */
    private $httpClientService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->taskNotificationService = self::$container->get(TaskNotificationService::class);
        $this->httpClientService = self::$container->get(HttpClientService::class);
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
        $this->httpClientService->appendFixtures($httpFixtures);

        $workerFactory = self::$container->get(WorkerFactory::class);
        foreach ($workerValuesCollection as $workerValues) {
            $workerFactory->create($workerValues);
        }

        $this->taskNotificationService->notify();

        $httpHistory = $this->httpClientService->getHistory();

        if (count($expectedHttpTransactions)) {
            foreach ($httpHistory as $httpTransactionIndex => $httpTransaction) {
                $expectedHttpTransaction = $expectedHttpTransactions[$httpTransactionIndex];
                $expectedRequest = $expectedHttpTransaction['request'];
                $expectedResponse = $expectedHttpTransaction['response'];

                /* @var RequestInterface $request */
                $request = $httpTransaction['request'];

                $this->assertEquals($expectedRequest['hostname'], $request->getHeaderLine('host'));

                /* @var ResponseInterface $response */
                $response = $httpTransaction['response'];

                if (is_null($expectedResponse)) {
                    $this->assertNull($response);
                } else {
                    $this->assertEquals($expectedResponse['statusCode'], $response->getStatusCode());
                }
            }
        } else {
            $this->assertCount(0, $httpHistory);
        }
    }

    /**
     * @return array
     */
    public function notifyDataProvider()
    {
        $serviceUnavailableResponse = new Response(503);
        $curl28ConnectException = ConnectExceptionFactory::create(28, 'Operation timed out');

        $expectedServiceUnavailableHttpTransaction = [
            'request' => [
                'hostname' => 'worker.simplytestable.com',
            ],
            'response' => [
                'statusCode' => 503,
            ],
        ];

        $expectedCurl38ExceptionHttpTransaction = [
            'request' => [
                'hostname' => 'worker.simplytestable.com',
            ],
            'response' => null,
        ];

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
                    new Response(400),
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
                    $serviceUnavailableResponse,
                    $serviceUnavailableResponse,
                    $serviceUnavailableResponse,
                    $serviceUnavailableResponse,
                    $serviceUnavailableResponse,
                    $serviceUnavailableResponse,
                ],
                'workerValuesCollection' => [
                    [
                        WorkerFactory::KEY_HOSTNAME => 'worker.simplytestable.com',
                        WorkerFactory::KEY_STATE => Worker::STATE_ACTIVE,
                    ],
                ],
                'expectedHttpTransactions' => [
                    $expectedServiceUnavailableHttpTransaction,
                    $expectedServiceUnavailableHttpTransaction,
                    $expectedServiceUnavailableHttpTransaction,
                    $expectedServiceUnavailableHttpTransaction,
                    $expectedServiceUnavailableHttpTransaction,
                    $expectedServiceUnavailableHttpTransaction,
                ],
            ],
            'single active worker, curl error response' => [
                'httpFixtures' => [
                    $curl28ConnectException,
                    $curl28ConnectException,
                    $curl28ConnectException,
                    $curl28ConnectException,
                    $curl28ConnectException,
                    $curl28ConnectException,
                ],
                'workerValuesCollection' => [
                    [
                        WorkerFactory::KEY_HOSTNAME => 'worker.simplytestable.com',
                        WorkerFactory::KEY_STATE => Worker::STATE_ACTIVE,
                    ],
                ],
                'expectedHttpTransactions' => [
                    $expectedCurl38ExceptionHttpTransaction,
                    $expectedCurl38ExceptionHttpTransaction,
                    $expectedCurl38ExceptionHttpTransaction,
                    $expectedCurl38ExceptionHttpTransaction,
                    $expectedCurl38ExceptionHttpTransaction,
                    $expectedCurl38ExceptionHttpTransaction,
                ],
            ],
            'many workers, mixed responses' => [
                'httpFixtures' => [
                    new Response(),
                    new Response(404),
                    new Response(),
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
