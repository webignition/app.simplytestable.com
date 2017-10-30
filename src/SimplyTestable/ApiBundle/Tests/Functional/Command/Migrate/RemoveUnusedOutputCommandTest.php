<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Migrate;

use SimplyTestable\ApiBundle\Command\Migrate\RemoveUnusedOutputCommand;
use SimplyTestable\ApiBundle\Entity\Task\Output;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\TaskOutputFactory;
use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class RemoveUnusedOutputCommandTest extends AbstractBaseTestCase
{
    /**
     * @var RemoveUnusedOutputCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = $this->container->get('simplytestable.command.migrate.removeunusedoutput');
    }

    public function testRunCommandInMaintenanceReadOnlyMode()
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        $returnCode = $this->command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(
            RemoveUnusedOutputCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );

        $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
    }

    /**
     * @dataProvider runDataProvider
     *
     * @param array $jobValuesCollection
     * @param array $taskOutputValuesCollection
     * @param int[] $taskIndicesForWhichToNullifyOutput
     * @param array $args
     * @param int[] $expectedTaskOutputIndicesInUse
     */
    public function testRun(
        $jobValuesCollection,
        $taskOutputValuesCollection,
        $taskIndicesForWhichToNullifyOutput,
        $args,
        $expectedTaskOutputIndicesInUse
    ) {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $jobFactory = new JobFactory($this->container);
        $taskOutputFactory = new TaskOutputFactory($this->container);

        $jobs = $jobFactory->createResolveAndPrepareCollection($jobValuesCollection);

        /* @var Task[] $tasks */
        $tasks = [];

        foreach ($jobs as $job) {
            $tasks = array_merge($tasks, $job->getTasks()->toArray());
        }

        $expectedTaskOutputIdsInUse = [];

        foreach ($tasks as $taskIndex => $task) {
            if (isset($taskOutputValuesCollection[$taskIndex]) && !is_null($taskOutputValuesCollection[$taskIndex])) {
                $taskOutputValues = $taskOutputValuesCollection[$taskIndex];
                $taskOutputFactory->create($task, $taskOutputValues);
            }

            $taskOutputIds[] = $task->getOutput()->getId();

            if (in_array($taskIndex, $expectedTaskOutputIndicesInUse)) {
                $expectedTaskOutputIdsInUse[] = $task->getOutput()->getId();
            }

            if (in_array($taskIndex, $taskIndicesForWhichToNullifyOutput)) {
                $task->setOutput(null);

                $entityManager->persist($task);
                $entityManager->flush();
            }
        }

        $returnCode = $this->command->run(new ArrayInput($args), new BufferedOutput());

        $this->assertEquals(RemoveUnusedOutputCommand::RETURN_CODE_OK, $returnCode);

        $taskOutputRepository = $entityManager->getRepository(Output::class);

        /* @var Output[] $allTaskOutput */
        $allTaskOutput = $taskOutputRepository->findAll();

        $taskOutputIdsInUse = [];

        foreach ($allTaskOutput as $taskOutput) {
            $taskOutputIdsInUse[] = $taskOutput->getId();
        }

        $this->assertEquals($expectedTaskOutputIdsInUse, $taskOutputIdsInUse);
    }

    /**
     * @return array
     */
    public function runDataProvider()
    {
        return [
            'no unused output' => [
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
                'taskIndicesForWhichToNullifyOutput' => [],
                'args' => [],
                'expectedTaskOutputIndicesInUse' => [0, 1, 2],
            ],
            'has unused output' => [
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
                'taskIndicesForWhichToNullifyOutput' => [0, 2],
                'args' => [],
                'expectedTaskOutputIndicesInUse' => [1],
            ],
            'flush-threshold' => [
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
                'taskIndicesForWhichToNullifyOutput' => [0, 2],
                'args' => [
                    '--flush-threshold' => 2,
                ],
                'expectedTaskOutputIndicesInUse' => [1],
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
                        TaskOutputFactory::KEY_OUTPUT => 'bar',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foobar',
                    ],
                ],
                'taskIndicesForWhichToNullifyOutput' => [0, 2],
                'args' => [
                    '--limit' => 'foo',
                ],
                'expectedTaskOutputIndicesInUse' => [1],
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
                        TaskOutputFactory::KEY_OUTPUT => 'bar',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foobar',
                    ],
                ],
                'taskIndicesForWhichToNullifyOutput' => [0, 2],
                'args' => [
                    '--limit' => -1,
                ],
                'expectedTaskOutputIndicesInUse' => [1],
            ],
            'limit: 1' => [
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
                'taskIndicesForWhichToNullifyOutput' => [0, 1],
                'args' => [
                    '--limit' => 1,
                ],
                'expectedTaskOutputIndicesInUse' => [0, 2],
            ],
        ];
    }
}
