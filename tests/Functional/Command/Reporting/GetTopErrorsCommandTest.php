<?php

namespace App\Tests\Functional\Command\Reporting;

use App\Command\Reporting\GetTopErrorsCommand;
use App\Entity\Task\Task;
use App\Tests\Factory\HtmlValidatorOutputFactory;
use App\Tests\Services\TaskOutputFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\JobFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class GetTopErrorsCommandTest extends AbstractBaseTestCase
{
    /**
     * @var GetTopErrorsCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = self::$container->get(GetTopErrorsCommand::class);
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
                'expectedReturnCode' => GetTopErrorsCommand::RETURN_CODE_MISSING_TASK_TYPE,
            ],
            'task-type invalid' => [
                'args' => [
                    '--task-type' => 'foo',
                ],
                'expectedReturnCode' => GetTopErrorsCommand::RETURN_CODE_INVALID_TASK_TYPE,
            ],
        ];
    }

    /**
     * @dataProvider runDataProvider
     *
     * @param array $jobValuesCollection
     * @param array $taskOutputValuesCollection
     * @param array $args
     * @param int[] $expectedReportData
     */
    public function testRun(
        $jobValuesCollection,
        $taskOutputValuesCollection,
        $args,
        $expectedReportData
    ) {
        $jobFactory = self::$container->get(JobFactory::class);
        $taskOutputFactory = self::$container->get(TaskOutputFactory::class);

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

        $commandOutput = new BufferedOutput();

        $returnCode = $this->command->run(new ArrayInput($args), $commandOutput);

        $this->assertEquals(GetTopErrorsCommand::RETURN_CODE_OK, $returnCode);

        $reportData = json_decode($commandOutput->fetch(), true);

        $this->assertEquals($expectedReportData, $reportData);
    }

    /**
     * @return array
     */
    public function runDataProvider()
    {
        $htmlValidatorOutputFactory = new HtmlValidatorOutputFactory();

        return [
            'with error content, not normalised, type filter = R' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => json_encode($htmlValidatorOutputFactory->create([
                            [
                                HtmlValidatorOutputFactory::KEY_MESSAGE_INDEX => 0,
                            ],
                            [
                                HtmlValidatorOutputFactory::KEY_MESSAGE_INDEX => 1,
                            ],
                        ])),
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
                    '--type-filter' =>  'R',
                ],
                'expectedReportData' => null,
            ],
            'with error content, not normalised, type filter = R, error only' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => json_encode($htmlValidatorOutputFactory->create([
                            [
                                HtmlValidatorOutputFactory::KEY_MESSAGE_INDEX => 0,
                            ],
                            [
                                HtmlValidatorOutputFactory::KEY_MESSAGE_INDEX => 1,
                            ],
                        ])),
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
                    '--type-filter' =>  'R',
                    '--error-only' => '1',
                ],
                'expectedReportData' => null,
            ],
            'with error content, normalised' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => json_encode($htmlValidatorOutputFactory->create([
                            [
                                HtmlValidatorOutputFactory::KEY_MESSAGE_INDEX => 0,
                            ],
                            [
                                HtmlValidatorOutputFactory::KEY_MESSAGE_INDEX => 1,
                            ],
                        ])),
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
                    '--normalise' => '1',
                    '--report-only' => '1',
                ],
                'expectedReportData' => [
                    [
                        'count' => 1,
                        'normal_form' => 'An img element must have an alt attribute, except under certain conditions. '
                            . 'For details, consult guidance on providing text alternatives for images.',
                    ],
                    [
                        'count' => 1,
                        'normal_form' => 'Bad value %0 for attribute %1 on element %2: %3',
                        'parameters' => [
                            'text/html; charset=UTF-8' => [
                                'count' => 1,
                                'children' => [
                                    'content' => [
                                        'count' => 1,
                                        'children' => [
                                            'meta' => [
                                                'count' => 1,
                                                'children' => [
                                                    'utf-8 is not a valid character encoding name.' => [
                                                        'count' => 1,
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'with error content, normalised, type filter = N' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => json_encode($htmlValidatorOutputFactory->create([
                            [
                                HtmlValidatorOutputFactory::KEY_MESSAGE_INDEX => 0,
                            ],
                            [
                                HtmlValidatorOutputFactory::KEY_MESSAGE_INDEX => 1,
                            ],
                        ])),
                        TaskOutputFactory::KEY_ERROR_COUNT => 1,
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
                    '--normalise' => '1',
                    '--report-only' => '1',
                    '--type-filter' => 'N',
                ],
                'expectedReportData' => [
                    [
                        'count' => 1,
                        'normal_form' => 'Bad value %0 for attribute %1 on element %2: %3',
                        'parameters' => [
                            'text/html; charset=UTF-8' => [
                                'count' => 1,
                                'children' => [
                                    'content' => [
                                        'count' => 1,
                                        'children' => [
                                            'meta' => [
                                                'count' => 1,
                                                'children' => [
                                                    'utf-8 is not a valid character encoding name.' => [
                                                        'count' => 1,
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
