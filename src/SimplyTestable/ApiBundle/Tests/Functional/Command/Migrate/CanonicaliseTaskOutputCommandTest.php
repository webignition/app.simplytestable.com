<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Migrate;

use SimplyTestable\ApiBundle\Command\Migrate\CanonicaliseTaskOutputCommand;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\TaskOutputFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CanonicaliseTaskOutputCommandTest extends BaseSimplyTestableTestCase
{
    /**
     * @var CanonicaliseTaskOutputCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = $this->container->get('simplytestable.command.migrate.canonicalisetaskoutput');
    }

    public function testRunCommandInMaintenanceReadOnlyMode()
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        $returnCode = $this->command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(
            CanonicaliseTaskOutputCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );

        $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
    }

    public function testRunWithOnlyUnusedDuplicateHashes()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $jobFactory = new JobFactory($this->container);
        $taskOutputFactory = new TaskOutputFactory($this->container);

        $job = $jobFactory->createResolveAndPrepare();

        /* @var Task[] $tasks */
        $tasks = $job->getTasks()->toArray();

        foreach ($tasks as $taskIndex => $task) {
            $taskOutputFactory->create($task, [
                TaskOutputFactory::KEY_OUTPUT => 'foo',
            ]);

            $task->setOutput(null);

            $entityManager->persist($task);
            $entityManager->flush();
        }

        $returnCode = $this->command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(CanonicaliseTaskOutputCommand::RETURN_CODE_OK, $returnCode);
    }

    /**
     * @dataProvider runDataProvider
     *
     * @param array $jobValuesCollection
     * @param array $taskOutputValuesCollection
     * @param array $args
     * @param int[] $expectedTaskOutputIndicesInUse
     */
    public function testRun(
        $jobValuesCollection,
        $taskOutputValuesCollection,
        $args,
        $expectedTaskOutputIndicesInUse
    ) {
        $jobFactory = new JobFactory($this->container);
        $taskOutputFactory = new TaskOutputFactory($this->container);

        $jobs = $jobFactory->createResolveAndPrepareCollection($jobValuesCollection);

        /* @var Task[] $tasks */
        $tasks = [];

        foreach ($jobs as $job) {
            $tasks = array_merge($tasks, $job->getTasks()->toArray());
        }

        $expectedTaskOutputIdsInUse = [];
        $taskOutputIds = [];

        foreach ($tasks as $taskIndex => $task) {
            if (isset($taskOutputValuesCollection[$taskIndex]) && !is_null($taskOutputValuesCollection[$taskIndex])) {
                $taskOutputValues = $taskOutputValuesCollection[$taskIndex];
                $taskOutputFactory->create($task, $taskOutputValues);
            }

            $taskOutputIds[] = $task->getOutput()->getId();

            if (in_array($taskIndex, $expectedTaskOutputIndicesInUse)) {
                $expectedTaskOutputIdsInUse[] = $task->getOutput()->getId();
            }
        }

        $returnCode = $this->command->run(new ArrayInput($args), new BufferedOutput());

        $this->assertEquals(CanonicaliseTaskOutputCommand::RETURN_CODE_OK, $returnCode);

        $taskOutputIdsInUse = [];

        foreach ($tasks as $task) {
            $taskOutputIdsInUse[] = $task->getOutput()->getId();
        }

        $taskOutputIdsInUse = array_values(array_unique($taskOutputIdsInUse));

        $this->assertEquals($expectedTaskOutputIdsInUse, $taskOutputIdsInUse);
    }

    /**
     * @return array
     */
    public function runDataProvider()
    {
        return [
            'no duplicate hashes' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'bar',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foobar',
                    ],
                ],
                'args' => [],
                'expectedTaskOutputIndicesInUse' => [0, 1, 2],
            ],
            'some duplicate hashes' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'bar',
                    ],
                ],
                'args' => [],
                'expectedTaskOutputIndicesInUse' => [0, 2],
            ],
            'all duplicate hashes' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                    ],
                ],
                'args' => [],
                'expectedTaskOutputIndicesInUse' => [0],
            ],
            'limit: foo' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                    ],
                ],
                'args' => [
                    '--limit' => 'foo',
                ],
                'expectedTaskOutputIndicesInUse' => [0],
            ],
            'limit: -1' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                    ],
                ],
                'args' => [
                    '--limit' => -1,
                ],
                'expectedTaskOutputIndicesInUse' => [0],
            ],
            'limit: 2' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://foo.example.com',
                        JobFactory::KEY_DOMAIN => 'foo.example.com',
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://bar.example.com',
                        JobFactory::KEY_DOMAIN => 'bar.example.com',
                    ],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'bar',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'bar',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foobar',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foobar',
                    ],
                ],
                'args' => [
                    '--limit' => 2,
                ],
                'expectedTaskOutputIndicesInUse' => [0, 1, 2, 4],
            ],
            'dry-run' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                    ],
                ],
                'args' => [
                    '--dry-run' => true,
                ],
                'expectedTaskOutputIndicesInUse' => [0, 1, 2],
            ],
        ];
    }
}
