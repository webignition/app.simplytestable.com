<?php

namespace Tests\ApiBundle\Functional\Command\Task\Cancel;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use SimplyTestable\ApiBundle\Command\Task\Cancel\CollectionCommand;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use Tests\ApiBundle\Factory\ConnectExceptionFactory;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\WorkerFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\ApiBundle\Services\TestHttpClientService;

class CollectionCommandTest extends AbstractBaseTestCase
{
    /**
     * @var CollectionCommand
     */
    private $command;

    /**
     * @var WorkerFactory
     */
    private $workerFactory;

    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = self::$container->get(CollectionCommand::class);

        $this->workerFactory = new WorkerFactory(self::$container);
        $this->jobFactory = new JobFactory(self::$container);
    }

    /**
     * @dataProvider runDataProvider
     *
     * @param array $httpFixtures
     * @param array $workerValuesCollection
     * @param array $jobValues
     * @param array $expectedHttpTransactions
     * @param string[] $expectedTaskStates
     */
    public function testRun(
        $httpFixtures,
        $workerValuesCollection,
        $jobValues,
        $expectedHttpTransactions,
        $expectedTaskStates
    ) {
        /* @var TestHttpClientService $httpClientService */
        $httpClientService = self::$container->get(HttpClientService::class);

        foreach ($workerValuesCollection as $workerValues) {
            $this->workerFactory->create($workerValues);
        }

        $job = $this->jobFactory->createResolveAndPrepare($jobValues);
        $httpClientService->getHistory()->clear();

        $httpClientService->appendFixtures($httpFixtures);

        $returnCode = $this->command->run(new ArrayInput([
            'ids' => implode(',', $job->getTaskIds()),
        ]), new BufferedOutput());

        $this->assertEquals(CollectionCommand::RETURN_CODE_OK, $returnCode);

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

            $requestTaskIds = $postedData['ids'];

            $this->assertEquals($expectedRequest['hostname'], $request->getUri()->getHost());
            $this->assertEquals($expectedRequest['ids'], $requestTaskIds);

            /* @var ResponseInterface $response */
            $response = $httpTransaction['response'];

            if (is_null($expectedResponse)) {
                $this->assertNull($response);
            } else {
                $this->assertEquals($expectedResponse['statusCode'], $response->getStatusCode());
            }
        }

        foreach ($job->getTasks() as $taskIndex => $task) {
            $this->assertEquals(
                $expectedTaskStates[$taskIndex],
                $task->getState()->getName()
            );
        }
    }

    /**
     * @return array
     */
    public function runDataProvider()
    {
        $successResponse = new Response();
        $notFoundResponse = new Response(404);
        $curl28ConnectException = ConnectExceptionFactory::create('CURL/28 Operation timed out');

        return [
            'tasks have no worker' => [
                'httpFixtures' => [],
                'workerValuesCollection' => [],
                'jobValues' => [],
                'expectedHttpTransactions' => [],
                'expectedTaskStates' => [
                    Task::STATE_CANCELLED,
                    Task::STATE_CANCELLED,
                    Task::STATE_CANCELLED,
                ],
            ],
            'tasks have workers' => [
                'httpFixtures' => [
                    $successResponse,
                    $successResponse,
                ],
                'workerValuesCollection' => [
                    [
                        WorkerFactory::KEY_HOSTNAME => 'worker0',
                    ],
                    [
                        WorkerFactory::KEY_HOSTNAME => 'worker1',
                    ],
                    [
                        WorkerFactory::KEY_HOSTNAME => 'worker2',
                    ],
                ],
                'jobValues' => [
                    JobFactory::KEY_TASKS => [
                        [
                            JobFactory::KEY_TASK_WORKER_HOSTNAME => 'worker0',
                            JobFactory::KEY_TASK_REMOTE_ID => 1,
                        ],
                        [
                            JobFactory::KEY_TASK_WORKER_HOSTNAME => 'worker0',
                            JobFactory::KEY_TASK_REMOTE_ID => 2,
                        ],
                        [
                            JobFactory::KEY_TASK_WORKER_HOSTNAME => 'worker1',
                            JobFactory::KEY_TASK_REMOTE_ID => 3,
                        ],
                    ],
                ],
                'expectedHttpTransactions' => [
                    [
                        'request' => [
                            'hostname' => 'worker0',
                            'ids' => '1,2',
                        ],
                        'response' => [
                            'statusCode' => 200,
                        ],
                    ],
                    [
                        'request' => [
                            'hostname' => 'worker1',
                            'ids' => '3',
                        ],
                        'response' => [
                            'statusCode' => 200,
                        ],
                    ],
                ],
                'expectedTaskStates' => [
                    Task::STATE_CANCELLED,
                    Task::STATE_CANCELLED,
                    Task::STATE_CANCELLED,
                ],
            ],
            'tasks have workers; request failures' => [
                'httpFixtures' => [
                    $notFoundResponse,
                    $curl28ConnectException,
                    $successResponse,
                ],
                'workerValuesCollection' => [
                    [
                        WorkerFactory::KEY_HOSTNAME => 'worker0',
                    ],
                    [
                        WorkerFactory::KEY_HOSTNAME => 'worker1',
                    ],
                    [
                        WorkerFactory::KEY_HOSTNAME => 'worker2',
                    ],
                ],
                'jobValues' => [
                    JobFactory::KEY_TASKS => [
                        [
                            JobFactory::KEY_TASK_WORKER_HOSTNAME => 'worker0',
                            JobFactory::KEY_TASK_REMOTE_ID => 1,
                        ],
                        [
                            JobFactory::KEY_TASK_WORKER_HOSTNAME => 'worker1',
                            JobFactory::KEY_TASK_REMOTE_ID => 2,
                        ],
                        [
                            JobFactory::KEY_TASK_WORKER_HOSTNAME => 'worker2',
                            JobFactory::KEY_TASK_REMOTE_ID => 3,
                        ],
                    ],
                ],
                'expectedHttpTransactions' => [
                    [
                        'request' => [
                            'hostname' => 'worker0',
                            'ids' => '1',
                        ],
                        'response' => [
                            'statusCode' => 404,
                        ],
                    ],
                    [
                        'request' => [
                            'hostname' => 'worker1',
                            'ids' => '2',
                        ],
                        'response' => null,
                    ],
                    [
                        'request' => [
                            'hostname' => 'worker2',
                            'ids' => '3',
                        ],
                        'response' => [
                            'statusCode' => 200,
                        ],
                    ],
                ],
                'expectedTaskStates' => [
                    Task::STATE_CANCELLED,
                    Task::STATE_CANCELLED,
                    Task::STATE_CANCELLED,
                ],
            ],
        ];
    }
}
