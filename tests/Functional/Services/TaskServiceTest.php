<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services;

use App\Entity\Job\Job;
use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Entity\TimePeriod;
use App\Repository\TaskRepository;
use App\Services\StateService;
use App\Services\TaskService;
use App\Services\TaskTypeService;
use App\Tests\Services\TaskFactory;
use App\Tests\Services\TaskOutputFactory;
use App\Tests\Services\TimePeriodFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\JobFactory;
use Doctrine\ORM\EntityManagerInterface;
use webignition\InternetMediaType\InternetMediaType;

class TaskServiceTest extends AbstractBaseTestCase
{
    /**
     * @var TaskService
     */
    private $taskService;

    /**
     * @var Job
     */
    private $job;

    /**
     * @var Task
     */
    private $task;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->taskService = self::$container->get(TaskService::class);

        $jobFactory = self::$container->get(JobFactory::class);
        $this->job = $jobFactory->createResolveAndPrepare();

        $this->task = $this->job->getTasks()->get(0);
    }

    public function testCancelFinishedTask()
    {
        $stateService = self::$container->get(StateService::class);

        $finishedStateNames = $this->taskService->getFinishedStateNames();

        foreach ($finishedStateNames as $stateName) {
            $this->task->setState($stateService->get($stateName));

            $this->taskService->cancel($this->task);
            $this->assertEquals($stateName, (string) $this->task->getState());
        }
    }

    /**
     * @dataProvider cancelSuccessDataProvider
     *
     * @param array $taskValues
     */
    public function testCancelSuccess($taskValues)
    {
        if (isset($taskValues[TaskFactory::KEY_TIME_PERIOD])) {
            $timePeriodFactory = self::$container->get(TimePeriodFactory::class);
            $timePeriod = $timePeriodFactory->create($taskValues[TaskFactory::KEY_TIME_PERIOD]);

            $taskValues[TaskFactory::KEY_TIME_PERIOD] = $timePeriod;
        }

        $taskFactory = self::$container->get(TaskFactory::class);
        $taskFactory->update($this->task, $taskValues);

        $this->taskService->cancel($this->task);

        $this->assertEquals(Task::STATE_CANCELLED, (string) $this->task->getState());
        $this->assertInstanceOf(TimePeriod::class, $this->task->getTimePeriod());
        $this->assertInstanceOf(\DateTime::class, $this->task->getTimePeriod()->getStartDateTime());
        $this->assertInstanceOf(\DateTime::class, $this->task->getTimePeriod()->getEndDateTime());
    }

    public function cancelSuccessDataProvider(): array
    {
        return [
            'without start date time' => [
                'taskValues' => [],
            ],
            'with start date time' => [
                'taskValues' => [
                    TaskFactory::KEY_TIME_PERIOD => [
                        TimePeriodFactory::KEY_START_DATE_TIME => new \DateTime(),
                    ],
                ],
            ],
        ];
    }

    public function testSetAwaitingCancellationDisallowedStateName()
    {
        $stateService = self::$container->get(StateService::class);

        $disallowedStateNames = [
            Task::STATE_AWAITING_CANCELLATION,
            Task::STATE_CANCELLED,
            Task::STATE_COMPLETED,
        ];

        foreach ($disallowedStateNames as $stateName) {
            $this->task->setState($stateService->get($stateName));

            $this->taskService->setAwaitingCancellation($this->task);
            $this->assertEquals($stateName, (string) $this->task->getState());
        }
    }

    public function testSetAwaitingCancellationSuccess()
    {
        $this->taskService->setAwaitingCancellation($this->task);

        $this->assertEquals(Task::STATE_AWAITING_CANCELLATION, (string) $this->task->getState());
    }

    public function testIsFinished()
    {
        $stateService = self::$container->get(StateService::class);

        $finishedStateNames = $this->taskService->getFinishedStateNames();

        foreach ($finishedStateNames as $stateName) {
            $this->task->setState($stateService->get($stateName));

            $this->assertTrue($this->taskService->isFinished($this->task));
        }

        $unfinishedStateNames = [
            Task::STATE_QUEUED,
            Task::STATE_IN_PROGRESS,
            Task::STATE_AWAITING_CANCELLATION,
            Task::STATE_QUEUED_FOR_ASSIGNMENT,
        ];

        foreach ($unfinishedStateNames as $stateName) {
            $this->task->setState($stateService->get($stateName));

            $this->assertFalse($this->taskService->isFinished($this->task));
        }
    }

    public function testIsCancellable()
    {
        $stateService = self::$container->get(StateService::class);

        $cancellableStateNames = $this->taskService->getCancellableStateNames();

        foreach ($cancellableStateNames as $stateName) {
            $this->task->setState($stateService->get($stateName));

            $this->assertTrue($this->taskService->isCancellable($this->task));
        }

        $uncancellableStateNames = [
            Task::STATE_CANCELLED,
            Task::STATE_COMPLETED,
            Task::STATE_FAILED_NO_RETRY_AVAILABLE,
            Task::STATE_FAILED_RETRY_AVAILABLE,
            Task::STATE_FAILED_RETRY_LIMIT_REACHED,
            Task::STATE_SKIPPED
        ];

        foreach ($uncancellableStateNames as $stateName) {
            $this->task->setState($stateService->get($stateName));

            $this->assertFalse($this->taskService->isCancellable($this->task));
        }
    }

    public function testGetIncompleteStateNames()
    {
        $this->assertEquals([
            Task::STATE_IN_PROGRESS,
            Task::STATE_QUEUED,
            Task::STATE_QUEUED_FOR_ASSIGNMENT,
        ], $this->taskService->getIncompleteStateNames());
    }

    public function testPersist()
    {
        $entityManager = self::$container->get(EntityManagerInterface::class);
        $taskRepository = self::$container->get(TaskRepository::class);

        $originalTaskUrl = $this->task->getUrl();

        $this->task->setUrl('foo');
        $entityManager->persist($this->task);

        $entityManager->clear();

        $retrievedTask = $taskRepository->find($this->task->getId());

        $this->assertEquals($originalTaskUrl, $retrievedTask->getUrl());
        $this->assertEquals('foo', $this->task->getUrl());
    }

    public function testPersistAndFlush()
    {
        $entityManager = self::$container->get(EntityManagerInterface::class);
        $taskRepository = self::$container->get(TaskRepository::class);

        $this->task->setUrl('foo');

        $entityManager->persist($this->task);
        $entityManager->flush();

        $entityManager->clear();

        /* @var Task $retrievedTask */
        $retrievedTask = $taskRepository->find($this->task->getId());

        $this->assertEquals('foo', $retrievedTask->getUrl());
        $this->assertEquals('foo', $this->task->getUrl());
    }

    public function testSetStarted()
    {
        $this->assertEquals(Task::STATE_QUEUED, (string) $this->task->getState());
        $this->assertNull($this->task->getTimePeriod());

        $this->taskService->setStarted($this->task);

        $this->assertEquals(Task::STATE_IN_PROGRESS, (string) $this->task->getState());
        $this->assertInstanceOf(TimePeriod::class, $this->task->getTimePeriod());
        $this->assertInstanceOf(\DateTime::class, $this->task->getTimePeriod()->getStartDateTime());
        $this->assertNull($this->task->getTimePeriod()->getEndDateTime());
    }

    public function testCompleteIncorrectState()
    {
        $stateService = self::$container->get(StateService::class);
        $completedState = $stateService->get(Task::STATE_COMPLETED);

        $incorrectStateNames = $this->taskService->getFinishedStateNames();

        foreach ($incorrectStateNames as $stateName) {
            $this->task->setState($stateService->get($stateName));

            $this->taskService->complete($this->task, new \DateTime(), new Output(), $completedState);

            $this->assertEquals($stateName, (string) $this->task->getState());
        }
    }

    public function testCompleteHasExistingOutput()
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $stateService = self::$container->get(StateService::class);
        $completedState = $stateService->get(Task::STATE_COMPLETED);

        $output = new Output();
        $output->generateHash();

        $entityManager->persist($output);
        $entityManager->flush();

        $this->taskService->complete($this->task, new \DateTime(), $output, $completedState);

        $this->assertEquals($output->getId(), $this->task->getOutput()->getId());
    }

    /**
     * @dataProvider completeSuccessDataProvider
     *
     * @param array $taskValues
     * @param \DateTime $endDateTime
     * @param string $outputContent
     * @param string $stateName
     */
    public function testCompleteSuccess($taskValues, $endDateTime, $outputContent, $stateName)
    {
        if (!empty($taskValues)) {
            if (isset($taskValues[TaskFactory::KEY_TIME_PERIOD])) {
                $timePeriodFactory = self::$container->get(TimePeriodFactory::class);
                $timePeriod = $timePeriodFactory->create($taskValues[TaskFactory::KEY_TIME_PERIOD]);

                $taskValues[TaskFactory::KEY_TIME_PERIOD] = $timePeriod;
            }

            $taskFactory = self::$container->get(TaskFactory::class);
            $taskFactory->update($this->task, $taskValues);
        }

        $stateService = self::$container->get(StateService::class);
        $state = $stateService->get($stateName);

        $output = new Output();
        $output->setOutput($outputContent);

        $this->taskService->complete($this->task, $endDateTime, $output, $state);

        $this->assertInstanceOf(TimePeriod::class, $this->task->getTimePeriod());
        $this->assertInstanceOf(\DateTime::class, $this->task->getTimePeriod()->getStartDateTime());
        $this->assertInstanceOf(\DateTime::class, $this->task->getTimePeriod()->getEndDateTime());

        $this->assertEquals($outputContent, $this->task->getOutput()->getOutput());
        $this->assertEquals($stateName, (string) $this->task->getState());
    }

    /**
     * @return array
     */
    public function completeSuccessDataProvider()
    {
        return [
            'no time period' => [
                'taskValues' => [],
                'endDateTime' => new \DateTime('2010-01-01 12:00:00'),
                'outputContent' => 'foo',
                'stateName' => Task::STATE_COMPLETED,
            ],
            'has time period' => [
                'taskValues' => [
                    TaskFactory::KEY_TIME_PERIOD => [
                        TimePeriodFactory::KEY_START_DATE_TIME => new \DateTime(),
                    ],
                ],
                'endDateTime' => new \DateTime('2010-01-01 12:00:00'),
                'outputContent' => 'foo',
                'stateName' => Task::STATE_COMPLETED,
            ],
        ];
    }

    public function testGetAvailableStateNames()
    {
        $this->assertEquals([
            Task::STATE_CANCELLED,
            Task::STATE_QUEUED,
            Task::STATE_IN_PROGRESS,
            Task::STATE_COMPLETED,
            Task::STATE_AWAITING_CANCELLATION,
            Task::STATE_QUEUED_FOR_ASSIGNMENT,
            Task::STATE_FAILED_NO_RETRY_AVAILABLE,
            Task::STATE_FAILED_RETRY_AVAILABLE,
            Task::STATE_FAILED_RETRY_LIMIT_REACHED,
            Task::STATE_SKIPPED
        ], $this->taskService->getAvailableStateNames());
    }

    /**
     * @dataProvider getEquivalentTasksDataProvider
     *
     * @param array $taskValues
     * @param string $url
     * @param string $taskTypeName
     * @param string $parameterHash
     * @param string[] $stateNames
     * @param int[] $expectedEquivalentTaskIndices
     */
    public function testGetEquivalentTasks(
        $taskValues,
        $url,
        $taskTypeName,
        $parameterHash,
        $stateNames,
        $expectedEquivalentTaskIndices
    ) {
        $taskTypeService = self::$container->get(TaskTypeService::class);
        $stateService = self::$container->get(StateService::class);
        $taskFactory = self::$container->get(TaskFactory::class);

        /* @var Task[] $tasks */
        $tasks = $this->job->getTasks()->toArray();

        foreach ($tasks as $taskIndex => $task) {
            $currentTaskValues = $taskValues[$taskIndex];

            // d751713988987e9331980363e24189ce
            if (isset($currentTaskValues[TaskFactory::KEY_TYPE])) {
                $currentTaskType = $taskTypeService->get($currentTaskValues[TaskFactory::KEY_TYPE]);
                $currentTaskValues[TaskFactory::KEY_TYPE] = $currentTaskType;
            }

            if (isset($currentTaskValues[TaskFactory::KEY_STATE])) {
                $currentState = $stateService->get($currentTaskValues[TaskFactory::KEY_STATE]);
                $currentTaskValues[TaskFactory::KEY_STATE] = $currentState;
            }

            $taskFactory->update($task, $currentTaskValues);
        }

        $taskType = $taskTypeService->get($taskTypeName);
        $states = $stateService->getCollection($stateNames);

        $equivalentTasks = $this->taskService->getEquivalentTasks($url, $taskType, $parameterHash, $states);

        $this->assertCount(count($expectedEquivalentTaskIndices), $equivalentTasks);

        $equivalentTaskIds = [];
        $expectedEquivalentTaskIds = [];

        foreach ($equivalentTasks as $equivalentTaskIndex => $equivalentTask) {
            if (in_array($equivalentTaskIndex, $expectedEquivalentTaskIndices)) {
                $expectedEquivalentTaskIds[] = $equivalentTask->getId();
            }

            $equivalentTaskIds[] = $equivalentTask->getId();
        }

        $this->assertEquals($equivalentTaskIds, $equivalentTaskIds);
    }

    /**
     * @return array
     */
    public function getEquivalentTasksDataProvider()
    {
        return [
            'no matches' => [
                'taskValues' => [
                    [
                        TaskFactory::KEY_URL => 'foo',
                        TaskFactory::KEY_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                        TaskFactory::KEY_PARAMETERS => '',
                    ],
                    [
                        TaskFactory::KEY_URL => 'foo',
                        TaskFactory::KEY_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                        TaskFactory::KEY_PARAMETERS => '',
                    ],
                    [
                        TaskFactory::KEY_URL => 'foo',
                        TaskFactory::KEY_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                        TaskFactory::KEY_PARAMETERS => '',
                    ],
                ],
                'url' => 'bar',
                'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                'parameterHash' => '',
                'stateNames' => [],
                'expectedEquivalentTaskIndices' => [],
            ],
            'match all by url, task type, state' => [
                'taskValues' => [
                    [
                        TaskFactory::KEY_URL => 'foo',
                        TaskFactory::KEY_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                        TaskFactory::KEY_PARAMETERS => '',
                        TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                    ],
                    [
                        TaskFactory::KEY_URL => 'foo',
                        TaskFactory::KEY_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                        TaskFactory::KEY_PARAMETERS => '',
                        TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                    ],
                    [
                        TaskFactory::KEY_URL => 'foo',
                        TaskFactory::KEY_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                        TaskFactory::KEY_PARAMETERS => '',
                        TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                    ],
                ],
                'url' => 'foo',
                'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                'parameterHash' => '',
                'stateNames' => [
                    Task::STATE_COMPLETED,
                ],
                'expectedEquivalentTaskIndices' => [0, 1, 2],
            ],
            'match all by encoded url variant' => [
                'taskValues' => [
                    [
                        TaskFactory::KEY_URL => 'foo%20bar',
                        TaskFactory::KEY_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                        TaskFactory::KEY_PARAMETERS => '',
                        TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                    ],
                    [
                        TaskFactory::KEY_URL => 'foo bar',
                        TaskFactory::KEY_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                        TaskFactory::KEY_PARAMETERS => '',
                        TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                    ],
                    [
                        TaskFactory::KEY_URL => 'foo%20bar',
                        TaskFactory::KEY_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                        TaskFactory::KEY_PARAMETERS => '',
                        TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                    ],
                ],
                'url' => 'foo%20bar',
                'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                'parameterHash' => '',
                'stateNames' => [
                    Task::STATE_COMPLETED,
                ],
                'expectedEquivalentTaskIndices' => [0, 1, 2],
            ],
            'match all by decoded url variant' => [
                'taskValues' => [
                    [
                        TaskFactory::KEY_URL => 'foo%20bar',
                        TaskFactory::KEY_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                        TaskFactory::KEY_PARAMETERS => '',
                        TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                    ],
                    [
                        TaskFactory::KEY_URL => 'foo bar',
                        TaskFactory::KEY_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                        TaskFactory::KEY_PARAMETERS => '',
                        TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                    ],
                    [
                        TaskFactory::KEY_URL => 'foo%20bar',
                        TaskFactory::KEY_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                        TaskFactory::KEY_PARAMETERS => '',
                        TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                    ],
                ],
                'url' => 'foo bar',
                'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                'parameterHash' => '',
                'stateNames' => [
                    Task::STATE_COMPLETED,
                ],
                'expectedEquivalentTaskIndices' => [0, 1, 2],
            ],
            'match partial by state' => [
                'taskValues' => [
                    [
                        TaskFactory::KEY_URL => 'foo',
                        TaskFactory::KEY_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                        TaskFactory::KEY_PARAMETERS => '',
                        TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                    ],
                    [
                        TaskFactory::KEY_URL => 'foo',
                        TaskFactory::KEY_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                        TaskFactory::KEY_PARAMETERS => '',
                        TaskFactory::KEY_STATE => Task::STATE_CANCELLED,
                    ],
                    [
                        TaskFactory::KEY_URL => 'foo',
                        TaskFactory::KEY_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                        TaskFactory::KEY_PARAMETERS => '',
                        TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                    ],
                ],
                'url' => 'foo',
                'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                'parameterHash' => '',
                'stateNames' => [
                    Task::STATE_COMPLETED,
                ],
                'expectedEquivalentTaskIndices' => [0, 2],
            ],
            'match partial by task type' => [
                'taskValues' => [
                    [
                        TaskFactory::KEY_URL => 'foo',
                        TaskFactory::KEY_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                        TaskFactory::KEY_PARAMETERS => '',
                        TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                    ],
                    [
                        TaskFactory::KEY_URL => 'foo',
                        TaskFactory::KEY_TYPE => TaskTypeService::CSS_VALIDATION_TYPE,
                        TaskFactory::KEY_PARAMETERS => '',
                        TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                    ],
                    [
                        TaskFactory::KEY_URL => 'foo',
                        TaskFactory::KEY_TYPE => TaskTypeService::CSS_VALIDATION_TYPE,
                        TaskFactory::KEY_PARAMETERS => '',
                        TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                    ],
                ],
                'url' => 'foo',
                'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                'parameterHash' => '',
                'stateNames' => [
                    Task::STATE_COMPLETED,
                ],
                'expectedEquivalentTaskIndices' => [0],
            ],
            'match partial by parameter hash' => [
                'taskValues' => [
                    [
                        TaskFactory::KEY_URL => 'foo',
                        TaskFactory::KEY_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                        TaskFactory::KEY_PARAMETERS => 'bar',
                        TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                    ],
                    [
                        TaskFactory::KEY_URL => 'foo',
                        TaskFactory::KEY_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                        TaskFactory::KEY_PARAMETERS => 'bar',
                        TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                    ],
                    [
                        TaskFactory::KEY_URL => 'foo',
                        TaskFactory::KEY_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                        TaskFactory::KEY_PARAMETERS => '',
                        TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                    ],
                ],
                'url' => 'foo',
                'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                'parameterHash' => 'd41d8cd98f00b204e9800998ecf8427e',
                'stateNames' => [
                    Task::STATE_COMPLETED,
                ],
                'expectedEquivalentTaskIndices' => [2],
            ],
        ];
    }

    /**
     * @dataProvider expireDataProvider
     */
    public function testExpire(array $updatedTaskValues, bool $expectedTaskHasOutput, array $expectedOutputValues)
    {
        $taskFactory = self::$container->get(TaskFactory::class);
        $outputFactory = self::$container->get(TaskOutputFactory::class);

        /* @var Task[] $tasks */
        $tasks = $this->job->getTasks()->toArray();
        $task = $tasks[0];

        $previousOutputId = null;

        if (isset($updatedTaskValues[TaskFactory::KEY_OUTPUT])) {
            $outputValues = $updatedTaskValues[TaskFactory::KEY_OUTPUT];
            $output = $outputFactory->create($task, $outputValues);
            $updatedTaskValues[TaskFactory::KEY_OUTPUT] = $output;

            $previousOutputId = $output->getId();
        }

        $taskFactory->update($task, $updatedTaskValues);

        $this->assertEquals($expectedTaskHasOutput, !empty($task->getOutput()));

        $this->taskService->expire($task);

        $this->assertEquals(Task::STATE_EXPIRED, $task->getState());

        if (empty($expectedOutputValues)) {
            $this->assertNull($task->getOutput());
        } else {
            $output = $task->getOutput();

            $this->assertNotEquals($previousOutputId, $output->getId());

            $this->assertSame($expectedOutputValues[TaskOutputFactory::KEY_OUTPUT], $output->getOutput());
            $this->assertSame($expectedOutputValues[TaskOutputFactory::KEY_CONTENT_TYPE], $output->getContentType());
            $this->assertSame($expectedOutputValues[TaskOutputFactory::KEY_ERROR_COUNT], $output->getErrorCount());
            $this->assertSame($expectedOutputValues[TaskOutputFactory::KEY_WARNING_COUNT], $output->getWarningCount());
            $this->assertSame($expectedOutputValues[TaskOutputFactory::KEY_HASH], $output->getHash());
        }

        $this->assertTrue(true);
    }

    public function expireDataProvider(): array
    {
        return [
            'task not has output' => [
                'updatedTaskValues' => [],
                'expectedHasOutput' => false,
                'expectedOutputValues' => [],
            ],
            'task has output' => [
                'updatedTaskValues' => [
                    TaskFactory::KEY_OUTPUT => [
                        TaskOutputFactory::KEY_OUTPUT => 'output content',
                        TaskOutputFactory::KEY_CONTENT_TYPE => new InternetMediaType('text', 'plain'),
                        TaskOutputFactory::KEY_ERROR_COUNT => 1,
                        TaskOutputFactory::KEY_WARNING_COUNT => 2,
                        TaskOutputFactory::KEY_HASH => 'output-1-hash',
                    ],
                ],
                'expectedHasOutput' => true,
                'expectedOutputValues' => [
                    TaskOutputFactory::KEY_OUTPUT => null,
                    TaskOutputFactory::KEY_CONTENT_TYPE => null,
                    TaskOutputFactory::KEY_ERROR_COUNT => 1,
                    TaskOutputFactory::KEY_WARNING_COUNT => 2,
                    TaskOutputFactory::KEY_HASH => 'e02617653f512665006c7e7cc45ba070',
                ],
            ],
        ];
    }
}
