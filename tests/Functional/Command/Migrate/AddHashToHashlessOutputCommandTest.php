<?php

namespace App\Tests\Functional\Command\Migrate;

use App\Command\Migrate\AddHashToHashlessOutputCommand;
use App\Entity\Task\Task;
use App\Tests\Factory\JobFactory;
use App\Tests\Factory\TaskOutputFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class AddHashToHashlessOutputCommandTest extends AbstractBaseTestCase
{
    /**
     * @var AddHashToHashlessOutputCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = self::$container->get(AddHashToHashlessOutputCommand::class);
    }

    /**
     * @dataProvider runDataProvider
     *
     * @param array $jobValuesCollection
     * @param array $taskOutputValuesCollection
     * @param array $args
     * @param int[] $expectedHashedTaskOutputIndices
     */
    public function testRun($jobValuesCollection, $taskOutputValuesCollection, $args, $expectedHashedTaskOutputIndices)
    {
        $jobFactory = new JobFactory(self::$container);
        $taskOutputFactory = new TaskOutputFactory(self::$container);

        $jobs = $jobFactory->createResolveAndPrepareCollection($jobValuesCollection);

        /* @var Task[] $tasks */
        $tasks = [];

        foreach ($jobs as $job) {
            $tasks = array_merge($tasks, $job->getTasks()->toArray());
        }

        $expectedHashedTaskOutputIds = [];

        foreach ($tasks as $taskIndex => $task) {
            if (isset($taskOutputValuesCollection[$taskIndex]) && !is_null($taskOutputValuesCollection[$taskIndex])) {
                $taskOutputValues = $taskOutputValuesCollection[$taskIndex];
                $taskOutputFactory->create($task, $taskOutputValues);
            }

            if (in_array($taskIndex, $expectedHashedTaskOutputIndices)) {
                $expectedHashedTaskOutputIds[] = $task->getId();
            }
        }

        $returnCode = $this->command->run(new ArrayInput($args), new BufferedOutput());

        $this->assertEquals(AddHashToHashlessOutputCommand::RETURN_CODE_OK, $returnCode);

        $hashedTaskOutputIds = [];

        foreach ($tasks as $taskIndex => $task) {
            if (!is_null($task->getOutput()->getHash())) {
                $hashedTaskOutputIds[] = $task->getId();
            }
        }

        $this->assertEquals($expectedHashedTaskOutputIds, $hashedTaskOutputIds);
    }

    /**
     * @return array
     */
    public function runDataProvider()
    {
        return [
            'no hashless output' => [
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
                'expectedHashedTaskOutputIndices' => [0, 1, 2],
            ],
            'has hashless output' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                        TaskOutputFactory::KEY_HASH => null,
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                        TaskOutputFactory::KEY_HASH => null,
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'bar',
                        TaskOutputFactory::KEY_HASH => null,
                    ],
                ],
                'args' => [],
                'expectedHashedTaskOutputIndices' => [0, 1, 2],
            ],
            'limit: foo' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                        TaskOutputFactory::KEY_HASH => null,
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                        TaskOutputFactory::KEY_HASH => null,
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'bar',
                        TaskOutputFactory::KEY_HASH => null,
                    ],
                ],
                'args' => [
                    '--limit' => 'foo',
                ],
                'expectedHashedTaskOutputIndices' => [0, 1, 2],
            ],
            'limit: -1' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                        TaskOutputFactory::KEY_HASH => null,
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                        TaskOutputFactory::KEY_HASH => null,
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'bar',
                        TaskOutputFactory::KEY_HASH => null,
                    ],
                ],
                'args' => [
                    '--limit' => -1,
                ],
                'expectedHashedTaskOutputIndices' => [0, 1, 2],
            ],
            'limit: 2' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                        TaskOutputFactory::KEY_HASH => null,
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                        TaskOutputFactory::KEY_HASH => null,
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'bar',
                        TaskOutputFactory::KEY_HASH => null,
                    ],
                ],
                'args' => [
                    '--limit' => 2,
                ],
                'expectedHashedTaskOutputIndices' => [0, 1],
            ],
            'dry-run' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                        TaskOutputFactory::KEY_HASH => null,
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                        TaskOutputFactory::KEY_HASH => null,
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'bar',
                        TaskOutputFactory::KEY_HASH => null,
                    ],
                ],
                'args' => [
                    '--dry-run' => true,
                ],
                'expectedHashedTaskOutputIndices' => [],
            ],
        ];
    }
}
