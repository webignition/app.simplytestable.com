<?php

namespace App\Tests\Functional\Command\Task\Assign;

use GuzzleHttp\Psr7\Response;
use App\Command\Task\Assign\CollectionCommand;
use App\Entity\Task\Task;
use App\Services\HttpClientService;
use App\Services\Resque\QueueService;
use App\Tests\Factory\JobFactory;
use App\Tests\Factory\SitemapFixtureFactory;
use App\Tests\Factory\WorkerFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use App\Tests\Services\TestHttpClientService;

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

    public function testRunNoTaskIds()
    {
        $returnCode = $this->command->run(new ArrayInput([
            'ids' => ''
        ]), new BufferedOutput());

        $this->assertEquals(CollectionCommand::RETURN_CODE_OK, $returnCode);
    }

    /**
     * @dataProvider runDataProvider
     *
     * @param array $resolveAndPrepareHttpFixtures
     * @param array $httpFixtures
     * @param array $workerValuesCollection
     * @param bool $assignAsRange
     * @param array $additionalArgs
     * @param int $expectedReturnCode
     * @param array $expectedTaskValuesCollection
     * @param bool $expectedTaskAssignCollectionQueueIsEmpty
     * @throws \Exception
     */
    public function testRun(
        array $resolveAndPrepareHttpFixtures,
        array $httpFixtures,
        array $workerValuesCollection,
        $assignAsRange,
        array $additionalArgs,
        $expectedReturnCode,
        array $expectedTaskValuesCollection,
        $expectedTaskAssignCollectionQueueIsEmpty
    ) {
        /* @var TestHttpClientService $httpClientService */
        $httpClientService = self::$container->get(HttpClientService::class);
        $resqueQueueService = self::$container->get(QueueService::class);
        $resqueQueueService->getResque()->getQueue('task-assign-collection')->clear();

        $job = $this->jobFactory->createResolveAndPrepare([], $resolveAndPrepareHttpFixtures);

        $httpClientService->appendFixtures($httpFixtures);

        foreach ($workerValuesCollection as $workerValues) {
            $this->workerFactory->create($workerValues);
        }

        $jobTaskIds = $job->getTaskIds();
        $jobTaskIdsToAssign = $assignAsRange
            ? $jobTaskIds[0] . ':' . $jobTaskIds[count($jobTaskIds) - 1]
            : implode(',', $jobTaskIds);

        $returnCode = $this->command->run(
            new ArrayInput(
                array_merge([
                    'ids' => $jobTaskIdsToAssign,
                ], $additionalArgs)
            ),
            new BufferedOutput()
        );

        $this->assertEquals($expectedReturnCode, $returnCode);

        /* @var Task $task */
        foreach ($job->getTasks() as $taskIndex => $task) {
            $expectedTaskValues = $expectedTaskValuesCollection[$taskIndex];

            if (empty($expectedTaskValues['worker'])) {
                $this->assertNull($task->getWorker());
            } else {
                $this->assertEquals($expectedTaskValues['worker']['hostname'], $task->getWorker()->getHostname());
            }

            $this->assertEquals($expectedTaskValues['state'], $task->getState()->getName());
        }

        if ($expectedTaskAssignCollectionQueueIsEmpty) {
            $this->assertTrue($resqueQueueService->isEmpty('task-assign-collection'));
        } else {
            $this->assertTrue($resqueQueueService->contains(
                'task-assign-collection',
                [
                    'ids' => implode(',', $job->getTaskIds())
                ]
            ));
        }
    }

    /**
     * @return array
     */
    public function runDataProvider()
    {
        return [
            'no workers' => [
                'resolveAndPrepareHttpFixtures' => [],
                'httpFixtures' => [],
                'workerValuesCollection' => [],
                'assignAsRange' => false,
                'additionalArgs' => [],
                'expectedReturnCode' => CollectionCommand::RETURN_CODE_FAILED_NO_WORKERS,
                'expectedTaskValuesCollection' => [
                    [
                        'worker' => null,
                        'state' => Task::STATE_QUEUED,
                    ],
                    [
                        'worker' => null,
                        'state' => Task::STATE_QUEUED,
                    ],
                    [
                        'worker' => null,
                        'state' => Task::STATE_QUEUED,
                    ],
                ],
                'expectedTaskAssignCollectionQueueIsEmpty' => false,
            ],
            'no workers available' => [
                'resolveAndPrepareHttpFixtures' => [],
                'httpFixtures' => [
                    new Response(404),
                ],
                'workerValuesCollection' => [
                    [
                        WorkerFactory::KEY_HOSTNAME => 'hydrogen.worker.example.com',
                    ],
                ],
                'assignAsRange' => false,
                'additionalArgs' => [],
                'expectedReturnCode' => 2,
                'expectedTaskValuesCollection' => [
                    [
                        'worker' => null,
                        'state' => Task::STATE_QUEUED,
                    ],
                    [
                        'worker' => null,
                        'state' => Task::STATE_QUEUED,
                    ],
                    [
                        'worker' => null,
                        'state' => Task::STATE_QUEUED,
                    ],
                ],
                'expectedTaskAssignCollectionQueueIsEmpty' => false,
            ],
            'assign to specific worker; comma-separated' => [
                'resolveAndPrepareHttpFixtures' => [],
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'application/json'], json_encode([
                        [
                            'id' => 1,
                            'url' => 'http://example.com/one',
                            'type' => 'html validation',
                        ],
                        [
                            'id' => 2,
                            'url' => 'http://example.com/bar%20foo',
                            'type' => 'html validation',
                        ],
                        [
                            'id' => 3,
                            'url' => 'http://example.com/foo bar',
                            'type' => 'html validation',
                        ],
                    ])),
                ],
                'workerValuesCollection' => [
                    [
                        WorkerFactory::KEY_HOSTNAME => 'hydrogen.worker.example.com',
                    ],
                ],
                'assignAsRange' => false,
                'additionalArgs' => [
                    'worker' => 'hydrogen.worker.example.com',
                ],
                'expectedReturnCode' => CollectionCommand::RETURN_CODE_OK,
                'expectedTaskValuesCollection' => [
                    [
                        'worker' => [
                            'hostname' => 'hydrogen.worker.example.com'
                        ],
                        'state' => Task::STATE_IN_PROGRESS,
                    ],
                    [
                        'worker' => [
                            'hostname' => 'hydrogen.worker.example.com'
                        ],
                        'state' => Task::STATE_IN_PROGRESS,
                    ],
                    [
                        'worker' => [
                            'hostname' => 'hydrogen.worker.example.com'
                        ],
                        'state' => Task::STATE_IN_PROGRESS,
                    ],
                ],
                'expectedTaskAssignCollectionQueueIsEmpty' => true,
            ],
            'assign to specific worker; comma-separated, single task' => [
                'resolveAndPrepareHttpFixtures' => [
                    'prepare' => [
                        new Response(200, ['content-type' => 'text/plain'], 'sitemap: sitemap.xml'),
                        new Response(200, ['content-type' => 'application/xml'], SitemapFixtureFactory::generate([
                            'http://example.com/1',
                        ])),
                    ],
                ],
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'application/json'], json_encode([
                        [
                            'id' => 1,
                            'url' => 'http://example.com/one',
                            'type' => 'html validation',
                        ],
                    ])),
                ],
                'workerValuesCollection' => [
                    [
                        WorkerFactory::KEY_HOSTNAME => 'hydrogen.worker.example.com',
                    ],
                ],
                'assignAsRange' => false,
                'additionalArgs' => [
                    'worker' => 'hydrogen.worker.example.com',
                ],
                'expectedReturnCode' => CollectionCommand::RETURN_CODE_OK,
                'expectedTaskValuesCollection' => [
                    [
                        'worker' => [
                            'hostname' => 'hydrogen.worker.example.com'
                        ],
                        'state' => Task::STATE_IN_PROGRESS,
                    ],
                ],
                'expectedTaskAssignCollectionQueueIsEmpty' => true,
            ],
            'assign to specific worker; assign all, range' => [
                'resolveAndPrepareHttpFixtures' => [],
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'application/json'], json_encode([
                        [
                            'id' => 1,
                            'url' => 'http://example.com/one',
                            'type' => 'html validation',
                        ],
                        [
                            'id' => 2,
                            'url' => 'http://example.com/bar%20foo',
                            'type' => 'html validation',
                        ],
                        [
                            'id' => 3,
                            'url' => 'http://example.com/foo bar',
                            'type' => 'html validation',
                        ],
                    ])),
                ],
                'workerValuesCollection' => [
                    [
                        WorkerFactory::KEY_HOSTNAME => 'hydrogen.worker.example.com',
                    ],
                ],
                'assignAsRange' => true,
                'additionalArgs' => [
                    'worker' => 'hydrogen.worker.example.com',
                ],
                'expectedReturnCode' => CollectionCommand::RETURN_CODE_OK,
                'expectedTaskValuesCollection' => [
                    [
                        'worker' => [
                            'hostname' => 'hydrogen.worker.example.com'
                        ],
                        'state' => Task::STATE_IN_PROGRESS,
                    ],
                    [
                        'worker' => [
                            'hostname' => 'hydrogen.worker.example.com'
                        ],
                        'state' => Task::STATE_IN_PROGRESS,
                    ],
                    [
                        'worker' => [
                            'hostname' => 'hydrogen.worker.example.com'
                        ],
                        'state' => Task::STATE_IN_PROGRESS,
                    ],
                ],
                'expectedTaskAssignCollectionQueueIsEmpty' => true,
            ],
        ];
    }
}
