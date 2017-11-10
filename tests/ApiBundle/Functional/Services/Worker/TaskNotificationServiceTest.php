<?php

namespace Tests\ApiBundle\Functional\Services\Resque;

use Guzzle\Http\Message\Request;
use Guzzle\Http\Message\Response;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use SimplyTestable\ApiBundle\Services\Worker\TaskNotificationService;
use Tests\ApiBundle\Factory\CurlExceptionFactory;
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

        $this->taskNotificationService = $this->container->get(
            'simplytestable.services.worker.tasknotificationservice'
        );
    }

    /**
     * @dataProvider notifyDataProvider
     *
     * @param array $httpFixtures
     * @param array $workerValuesCollection
     * @param array $expectedRequests
     * @param array $expectedResponses
     */
    public function testNotify($httpFixtures, $workerValuesCollection, $expectedRequests, $expectedResponses)
    {
        $this->queueHttpFixtures($httpFixtures);

        $workerFactory = new WorkerFactory($this->container);
        foreach ($workerValuesCollection as $workerValues) {
            $workerFactory->create($workerValues);
        }

        $this->taskNotificationService->notify();

        $httpClientService = $this->container->get(HttpClientService::class);
        $httpClientService->get();

        $httpTransactions = $httpClientService->getHistoryPlugin()->getAll();

        $this->assertCount(count($expectedRequests), $httpTransactions);
        $this->assertCount(count($expectedResponses), $httpTransactions);

        foreach ($httpTransactions as $httpTransactionIndex => $httpTransaction) {
            /* @var Request $request */
            $request = $httpTransaction['request'];

            /* @var Response $response */
            $response = $httpTransaction['response'];

            $expectedRequestData = $expectedRequests[$httpTransactionIndex];
            $this->assertEquals($expectedRequestData['hostname'], $request->getUrl(true)->getHost());

            $expectedResponseData = $expectedResponses[$httpTransactionIndex];
            $this->assertEquals($expectedResponseData['statusCode'], $response->getStatusCode());
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
                'expectedRequests' => [],
                'expectedResponses' => [],
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
                'expectedRequests' => [
                    [
                        'hostname' => 'worker.simplytestable.com',
                    ],
                ],
                'expectedResponses' => [
                    [
                        'statusCode' => 400,
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
                'expectedRequests' => [
                    [
                        'hostname' => 'worker.simplytestable.com',
                    ],
                ],
                'expectedResponses' => [
                    [
                        'statusCode' => 503,
                    ],
                ],
            ],
            'single active worker, curl error response' => [
                'httpFixtures' => [
                    CurlExceptionFactory::create('Operation timed out', 28)
                ],
                'workerValuesCollection' => [
                    [
                        WorkerFactory::KEY_HOSTNAME => 'worker.simplytestable.com',
                        WorkerFactory::KEY_STATE => Worker::STATE_ACTIVE,
                    ],
                ],
                'expectedRequests' => [],
                'expectedResponses' => [],
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
                'expectedRequests' => [
                    [
                        'hostname' => 'worker1.simplytestable.com',
                    ],
                    [
                        'hostname' => 'worker2.simplytestable.com',
                    ],
                    [
                        'hostname' => 'worker3.simplytestable.com',
                    ],
                ],
                'expectedResponses' => [
                    [
                        'statusCode' => 200,
                    ],
                    [
                        'statusCode' => 404,
                    ],
                    [
                        'statusCode' => 200,
                    ],
                ],
            ],
        ];
    }
}
