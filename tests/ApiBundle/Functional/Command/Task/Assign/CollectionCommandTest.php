<?php

namespace Tests\ApiBundle\Functional\Command\Task\Assign;

use SimplyTestable\ApiBundle\Command\Task\Assign\CollectionCommand;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\Resque\QueueService;
use SimplyTestable\ApiBundle\Services\TaskService;
use Tests\ApiBundle\Factory\HttpFixtureFactory;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\WorkerFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

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

        $this->command = $this->container->get('simplytestable.command.task.assigncollection');

        $this->workerFactory = new WorkerFactory($this->container);
        $this->jobFactory = new JobFactory($this->container);
    }

    public function testRunInMaintenanceReadOnlyMode()
    {
        $applicationStateService = $this->container->get(ApplicationStateService::class);
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        $returnCode = $this->command->run(new ArrayInput([
            'ids' => '1,2,3'
        ]), new BufferedOutput());

        $this->assertEquals(CollectionCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $returnCode);

        $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
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
     * @param array $httpFixtures
     * @param array $workerValuesCollection
     * @param array $additionalArgs
     * @param int $expectedReturnCode
     * @param array $expectedTaskValuesCollection
     * @param bool $expectedTaskAssignCollectionQueueIsEmpty
     */
    public function testRun(
        $httpFixtures,
        $workerValuesCollection,
        $additionalArgs,
        $expectedReturnCode,
        $expectedTaskValuesCollection,
        $expectedTaskAssignCollectionQueueIsEmpty
    ) {
        $resqueQueueService = $this->container->get(QueueService::class);

        $job = $this->jobFactory->createResolveAndPrepare();

        $this->queueHttpFixtures($httpFixtures);

        foreach ($workerValuesCollection as $workerValues) {
            $this->workerFactory->create($workerValues);
        }

        $returnCode = $this->command->run(
            new ArrayInput(
                array_merge([
                    'ids' => implode(',', $job->getTaskIds())
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
                'httpFixtures' => [],
                'workerValuesCollection' => [],
                'additionalArgs' => [],
                'expectedReturnCode' => CollectionCommand::RETURN_CODE_FAILED_NO_WORKERS,
                'expectedTaskValuesCollection' => [
                    [
                        'worker' => null,
                        'state' => TaskService::QUEUED_STATE,
                    ],
                    [
                        'worker' => null,
                        'state' => TaskService::QUEUED_STATE,
                    ],
                    [
                        'worker' => null,
                        'state' => TaskService::QUEUED_STATE,
                    ],
                ],
                'expectedTaskAssignCollectionQueueIsEmpty' => false,
            ],
            'no workers available' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createNotFoundResponse(),
                ],
                'workerValuesCollection' => [
                    [
                        WorkerFactory::KEY_HOSTNAME => 'hydrogen.worker.example.com',
                    ],
                ],
                'additionalArgs' => [],
                'expectedReturnCode' => 2,
                'expectedTaskValuesCollection' => [
                    [
                        'worker' => null,
                        'state' => TaskService::QUEUED_STATE,
                    ],
                    [
                        'worker' => null,
                        'state' => TaskService::QUEUED_STATE,
                    ],
                    [
                        'worker' => null,
                        'state' => TaskService::QUEUED_STATE,
                    ],
                ],
                'expectedTaskAssignCollectionQueueIsEmpty' => false,
            ],
            'assign to specific worker' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createSuccessResponse(
                        'application/json',
                        json_encode([
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
                        ])
                    ),
                ],
                'workerValuesCollection' => [
                    [
                        WorkerFactory::KEY_HOSTNAME => 'hydrogen.worker.example.com',
                    ],
                ],
                'additionalArgs' => [
                    'worker' => 'hydrogen.worker.example.com',
                ],
                'expectedReturnCode' => CollectionCommand::RETURN_CODE_OK,
                'expectedTaskValuesCollection' => [
                    [
                        'worker' => [
                            'hostname' => 'hydrogen.worker.example.com'
                        ],
                        'state' => TaskService::IN_PROGRESS_STATE,
                    ],
                    [
                        'worker' => [
                            'hostname' => 'hydrogen.worker.example.com'
                        ],
                        'state' => TaskService::IN_PROGRESS_STATE,
                    ],
                    [
                        'worker' => [
                            'hostname' => 'hydrogen.worker.example.com'
                        ],
                        'state' => TaskService::IN_PROGRESS_STATE,
                    ],
                ],
                'expectedTaskAssignCollectionQueueIsEmpty' => true,
            ],
        ];
    }
}
