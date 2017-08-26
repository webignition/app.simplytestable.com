<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Reporting;

use SimplyTestable\ApiBundle\Command\Reporting\GetOutputIdsForErrorCommand;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\TaskOutputFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class GetOutputIdsForErrorCommandTest extends BaseSimplyTestableTestCase
{
    /**
     * @var GetOutputIdsForErrorCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = $this->container->get('simplytestable.command.reporting.getoutputidsforerror');
    }

    /**
     * @dataProvider runWithInvalidArgumentsDataProvider
     *
     * @param array $args
     * @param int $expectedReturnCode
     */
    public function testRunWithInvalidArguments($args, $expectedReturnCode)
    {
        $returnCode = $this->command->run(new ArrayInput($args), new BufferedOutput());

        $this->assertEquals($expectedReturnCode, $returnCode);
    }

    /**
     * @return array
     */
    public function runWithInvalidArgumentsDataProvider()
    {
        return [
            'task-type missing' => [
                'args' => [],
                'expectedReturnCode' => GetOutputIdsForErrorCommand::RETURN_CODE_MISSING_TASK_TYPE,
            ],
            'task-type invalid' => [
                'args' => [
                    '--task-type' => 'foo',
                ],
                'expectedReturnCode' => GetOutputIdsForErrorCommand::RETURN_CODE_INVALID_TASK_TYPE,
            ],
            'fragments missing' => [
                'args' => [
                    '--task-type' => 'html validation',
                ],
                'expectedReturnCode' => GetOutputIdsForErrorCommand::RETURN_CODE_MISSING_FRAGMENTS,
            ],
        ];
    }

    public function testRunWithFullOutput()
    {
        $args = [
            '--task-type' => 'html validation',
            '--fragments' => 'foo',
        ];

        $taskOutputValuesCollection = [
            [
                TaskOutputFactory::KEY_OUTPUT => 'null',
            ],
        ];

        $jobFactory = new JobFactory($this->container);
        $taskOutputFactory = new TaskOutputFactory($this->container);

        $job = $jobFactory->createResolveAndPrepare();

        /* @var Task[] $tasks */
        $tasks = $job->getTasks()->toArray();

        foreach ($tasks as $taskIndex => $task) {
            if (isset($taskOutputValuesCollection[$taskIndex])) {
                $taskOutputValues = $taskOutputValuesCollection[$taskIndex];
                $taskOutputFactory->create($task, $taskOutputValues);
            }
        }

        $returnCode = $this->command->run(new ArrayInput($args), new BufferedOutput());

        $this->assertEquals(GetOutputIdsForErrorCommand::RETURN_CODE_OK, $returnCode);
    }

    /**
     * @dataProvider runDataProvider
     *
     * @param array $jobValuesCollection
     * @param array $taskOutputValuesCollection
     * @param array $args
     * @param int[] $expectedTaskOutputIndices
     */
    public function testRun(
        $jobValuesCollection,
        $taskOutputValuesCollection,
        $args,
        $expectedTaskOutputIndices
    ) {
        $jobFactory = new JobFactory($this->container);
        $taskOutputFactory = new TaskOutputFactory($this->container);

        $jobs = $jobFactory->createResolveAndPrepareCollection($jobValuesCollection);

        /* @var Task[] $tasks */
        $tasks = [];

        foreach ($jobs as $job) {
            $tasks = array_merge($tasks, $job->getTasks()->toArray());
        }

        foreach ($tasks as $taskIndex => $task) {
            if (isset($taskOutputValuesCollection[$taskIndex])) {
                $taskOutputValues = $taskOutputValuesCollection[$taskIndex];
                $taskOutputFactory->create($task, $taskOutputValues);
            }
        }

        $expectedTaskOutputIds = [];
        foreach ($tasks as $taskIndex => $task) {
            if (in_array($taskIndex, $expectedTaskOutputIndices)) {
                $taskOutputId = $task->getOutput()->getId();

                if (!in_array($taskOutputId, $expectedTaskOutputIds)) {
                    $expectedTaskOutputIds[] = $task->getOutput()->getId();
                }
            }
        }

        $commandOutput = new BufferedOutput();

        $args['--output-only-ids'] = 1;

        $returnCode = $this->command->run(new ArrayInput($args), $commandOutput);

        $this->assertEquals(GetOutputIdsForErrorCommand::RETURN_CODE_OK, $returnCode);

        $commandOutputString = trim($commandOutput->fetch());

        $taskOutputIds = empty($commandOutputString)
            ? []
            : explode(',', $commandOutputString);

        $this->assertEquals($expectedTaskOutputIds, $taskOutputIds);
    }

    /**
     * @return array
     */
    public function runDataProvider()
    {
        return [
            'no matching tasks' => [
                'jobValuesCollection' => [],
                'taskOutputValuesCollection' => [],
                'args' => [
                    '--task-type' => 'html validation',
                    '--fragments' => 'foo, bar, WHEE ',
                ],
                'expectedTaskOutputIndices' => [],
            ],
            'multiple fragment match in single output' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => json_encode([
                            'messages' => [
                                [
                                    'message' => 'foo',
                                    'type' => 'error',
                                ],
                                [
                                    'message' => 'foop',
                                    'type' => 'error',
                                ],
                            ],
                        ]),
                        TaskOutputFactory::KEY_ERROR_COUNT => 2,
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'null',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'null',
                    ],
                ],
                'args' => [
                    '--task-type' => 'html validation',
                    '--fragments' => 'foo',
                ],
                'expectedTaskOutputIndices' => [0],
            ],
            'single fragment match in multiple outputs' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => json_encode([
                            'messages' => [
                                [
                                    'message' => 'foo',
                                    'type' => 'error',
                                ],
                                [
                                    'message' => 'foot',
                                    'type' => 'error',
                                ],
                            ],
                        ]),
                        TaskOutputFactory::KEY_ERROR_COUNT => 2,
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'null',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => json_encode([
                            'messages' => [
                                [
                                    'message' => 'foot',
                                    'type' => 'error',
                                ],
                                [
                                    'message' => 'fool',
                                    'type' => 'error',
                                ],
                            ],
                        ]),
                        TaskOutputFactory::KEY_ERROR_COUNT => 2,
                    ],
                ],
                'args' => [
                    '--task-type' => 'html validation',
                    '--fragments' => 'foo',
                ],
                'expectedTaskOutputIndices' => [0, 2],
            ],
            'multiple fragment match in multiple outputs' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => json_encode([
                            'messages' => [
                                [
                                    'message' => 'foo bar',
                                    'type' => 'error',
                                ],
                                [
                                    'message' => 'foot',
                                    'type' => 'error',
                                ],
                            ],
                        ]),
                        TaskOutputFactory::KEY_ERROR_COUNT => 2,
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => json_encode([
                            'messages' => [
                                [
                                    'message' => 'foot',
                                    'type' => 'error',
                                ],
                                [
                                    'message' => 'fool',
                                    'type' => 'error',
                                ],
                            ],
                        ]),
                        TaskOutputFactory::KEY_ERROR_COUNT => 2,
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => json_encode([
                            'messages' => [
                                [
                                    'message' => 'foobar',
                                    'type' => 'error',
                                ],
                                [
                                    'message' => 'fool',
                                    'type' => 'error',
                                ],
                            ],
                        ]),
                        TaskOutputFactory::KEY_ERROR_COUNT => 2,
                    ],
                ],
                'args' => [
                    '--task-type' => 'html validation',
                    '--fragments' => 'foo,bar',
                ],
                'expectedTaskOutputIndices' => [0, 2],
            ],
        ];
    }
}
