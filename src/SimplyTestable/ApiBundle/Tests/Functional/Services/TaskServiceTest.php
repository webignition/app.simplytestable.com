<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Output;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\TimePeriod;
use SimplyTestable\ApiBundle\Repository\TaskRepository;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\TaskFactory;
use SimplyTestable\ApiBundle\Tests\Factory\TimePeriodFactory;
use SimplyTestable\ApiBundle\Tests\Factory\WorkerFactory;
use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;

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

        $this->taskService = $this->container->get('simplytestable.services.taskservice');

        $jobFactory = new JobFactory($this->container);
        $this->job = $jobFactory->createResolveAndPrepare();

        $this->task = $this->job->getTasks()->get(0);
    }

    public function testCancelFinishedTask()
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');

        $finishedStateNames = $this->taskService->getFinishedStateNames();

        foreach ($finishedStateNames as $stateName) {
            $this->task->setState($stateService->fetch($stateName));

            $this->taskService->cancel($this->task);
            $this->assertEquals($stateName, $this->task->getState()->getName());
        }
    }

    /**
     * @return array
     */
    public function cancelFinishedTaskDataProvider()
    {
        return [
            TaskService::CANCELLED_STATE => [
                'stateName' => TaskService::CANCELLED_STATE,
            ],
            TaskService::COMPLETED_STATE => [
                'stateName' => TaskService::COMPLETED_STATE,
            ],
            TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE => [
                'stateName' => TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
            ],
            TaskService::TASK_FAILED_NO_RETRY_AVAILABLE_STATE => [
                'stateName' => TaskService::TASK_FAILED_NO_RETRY_AVAILABLE_STATE,
            ],
            TaskService::TASK_FAILED_RETRY_LIMIT_REACHED_STATE => [
                'stateName' => TaskService::TASK_FAILED_RETRY_LIMIT_REACHED_STATE,
            ],
            TaskService::TASK_SKIPPED_STATE => [
                'stateName' => TaskService::TASK_SKIPPED_STATE,
            ],
        ];
    }

    /**
     * @dataProvider cancelSuccessDataProvider
     *
     * @param array $taskValues
     */
    public function testCancelSuccess($taskValues)
    {
        if (isset($taskValues[TaskFactory::KEY_WORKER])) {
            $workerFactory = new WorkerFactory($this->container);
            $worker = $workerFactory->create([
                WorkerFactory::KEY_HOSTNAME => $taskValues[TaskFactory::KEY_WORKER],
            ]);

            $taskValues[TaskFactory::KEY_WORKER] = $worker;
        }

        if (isset($taskValues[TaskFactory::KEY_TIME_PERIOD])) {
            $timePeriodFactory = new TimePeriodFactory($this->container);
            $timePeriod = $timePeriodFactory->create($taskValues[TaskFactory::KEY_TIME_PERIOD]);

            $taskValues[TaskFactory::KEY_TIME_PERIOD] = $timePeriod;
        }

        $taskFactory = new TaskFactory($this->container);
        $taskFactory->update($this->task, $taskValues);

        $this->taskService->cancel($this->task);

        $this->assertEquals(TaskService::CANCELLED_STATE, $this->task->getState()->getName());
        $this->assertNull($this->task->getWorker());
        $this->assertInstanceOf(TimePeriod::class, $this->task->getTimePeriod());
        $this->assertInstanceOf(\DateTime::class, $this->task->getTimePeriod()->getStartDateTime());
        $this->assertInstanceOf(\DateTime::class, $this->task->getTimePeriod()->getEndDateTime());
    }

    /**
     * @return array
     */
    public function cancelSuccessDataProvider()
    {
        return [
            'with worker, without start date time' => [
                'taskValues' => [
                    TaskFactory::KEY_WORKER => 'worker.simplytestable.com',
                ],
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
        $stateService = $this->container->get('simplytestable.services.stateservice');

        $disallowedStateNames = [
            TaskService::AWAITING_CANCELLATION_STATE,
            TaskService::CANCELLED_STATE,
            TaskService::COMPLETED_STATE,
        ];

        foreach ($disallowedStateNames as $stateName) {
            $this->task->setState($stateService->fetch($stateName));

            $this->taskService->setAwaitingCancellation($this->task);
            $this->assertEquals($stateName, $this->task->getState()->getName());
        }
    }

    public function testSetAwaitingCancellationSuccess()
    {
        $this->taskService->setAwaitingCancellation($this->task);

        $this->assertEquals(TaskService::AWAITING_CANCELLATION_STATE, $this->task->getState()->getName());
    }

    public function testIsFinished()
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');

        $finishedStateNames = $this->taskService->getFinishedStateNames();

        foreach ($finishedStateNames as $stateName) {
            $this->task->setState($stateService->fetch($stateName));

            $this->assertTrue($this->taskService->isFinished($this->task));
        }

        $unfinishedStateNames = [
            TaskService::QUEUED_STATE,
            TaskService::IN_PROGRESS_STATE,
            TaskService::AWAITING_CANCELLATION_STATE,
            TaskService::QUEUED_FOR_ASSIGNMENT_STATE,
        ];

        foreach ($unfinishedStateNames as $stateName) {
            $this->task->setState($stateService->fetch($stateName));

            $this->assertFalse($this->taskService->isFinished($this->task));
        }
    }

    public function testIsCancellable()
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');

        $cancellableStateNames = $this->taskService->getCancellableStateNames();

        foreach ($cancellableStateNames as $stateName) {
            $this->task->setState($stateService->fetch($stateName));

            $this->assertTrue($this->taskService->isCancellable($this->task));
        }

        $uncancellableStateNames = [
            TaskService::CANCELLED_STATE,
            TaskService::COMPLETED_STATE,
            TaskService::TASK_FAILED_NO_RETRY_AVAILABLE_STATE,
            TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
            TaskService::TASK_FAILED_RETRY_LIMIT_REACHED_STATE,
            TaskService::TASK_SKIPPED_STATE
        ];

        foreach ($uncancellableStateNames as $stateName) {
            $this->task->setState($stateService->fetch($stateName));

            $this->assertFalse($this->taskService->isCancellable($this->task));
        }
    }

    public function testGetIncompleteStateNames()
    {
        $this->assertEquals([
            TaskService::IN_PROGRESS_STATE,
            TaskService::QUEUED_STATE,
            TaskService::QUEUED_FOR_ASSIGNMENT_STATE,
        ], $this->taskService->getIncompleteStateNames());
    }

    public function testPersist()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $originalTaskUrl = $this->task->getUrl();

        $this->task->setUrl('foo');
        $this->taskService->persist($this->task);

        $entityManager->clear();

        $retrievedTask = $this->taskService->getEntityRepository()->find($this->task->getId());

        $this->assertEquals($originalTaskUrl, $retrievedTask->getUrl());
        $this->assertEquals('foo', $this->task->getUrl());
    }

    public function testPersistAndFlush()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $this->task->setUrl('foo');
        $this->taskService->persistAndFlush($this->task);

        $entityManager->clear();

        $retrievedTask = $this->taskService->getEntityRepository()->find($this->task->getId());

        $this->assertEquals('foo', $retrievedTask->getUrl());
        $this->assertEquals('foo', $this->task->getUrl());
    }

    public function testSetStarted()
    {
        $this->assertEquals(TaskService::QUEUED_STATE, $this->task->getState()->getName());
        $this->assertNull($this->task->getWorker());
        $this->assertNull($this->task->getRemoteId());
        $this->assertNull($this->task->getTimePeriod());

        $workerFactory = new WorkerFactory($this->container);
        $worker = $workerFactory->create();
        $remoteId = 1;

        $this->taskService->setStarted($this->task, $worker, $remoteId);

        $this->assertEquals(TaskService::IN_PROGRESS_STATE, $this->task->getState()->getName());
        $this->assertEquals($worker, $this->task->getWorker());
        $this->assertEquals($remoteId, $this->task->getRemoteId());
        $this->assertInstanceOf(TimePeriod::class, $this->task->getTimePeriod());
        $this->assertInstanceOf(\DateTime::class, $this->task->getTimePeriod()->getStartDateTime());
        $this->assertNull($this->task->getTimePeriod()->getEndDateTime());
    }

    public function testCompleteIncorrectState()
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $completedState = $stateService->fetch(TaskService::COMPLETED_STATE);

        $incorrectStateNames = $this->taskService->getFinishedStateNames();

        foreach ($incorrectStateNames as $stateName) {
            $this->task->setState($stateService->fetch($stateName));

            $this->taskService->complete($this->task, new \DateTime(), new Output(), $completedState);

            $this->assertEquals($stateName, $this->task->getState()->getName());
        }
    }

    public function testCompleteHasExistingOutput()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $completedState = $stateService->fetch(TaskService::COMPLETED_STATE);

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
            if (isset($taskValues[TaskFactory::KEY_WORKER])) {
                $workerFactory = new WorkerFactory($this->container);
                $worker = $workerFactory->create([
                    WorkerFactory::KEY_HOSTNAME => $taskValues[TaskFactory::KEY_WORKER],
                ]);

                $taskValues[TaskFactory::KEY_WORKER] = $worker;
            }

            if (isset($taskValues[TaskFactory::KEY_TIME_PERIOD])) {
                $timePeriodFactory = new TimePeriodFactory($this->container);
                $timePeriod = $timePeriodFactory->create($taskValues[TaskFactory::KEY_TIME_PERIOD]);

                $taskValues[TaskFactory::KEY_TIME_PERIOD] = $timePeriod;
            }

            $taskFactory = new TaskFactory($this->container);
            $taskFactory->update($this->task, $taskValues);
        }

        $stateService = $this->container->get('simplytestable.services.stateservice');
        $state = $stateService->fetch($stateName);

        $output = new Output();
        $output->setOutput($outputContent);

        $this->taskService->complete($this->task, $endDateTime, $output, $state);

        $this->assertInstanceOf(TimePeriod::class, $this->task->getTimePeriod());
        $this->assertInstanceOf(\DateTime::class, $this->task->getTimePeriod()->getStartDateTime());
        $this->assertInstanceOf(\DateTime::class, $this->task->getTimePeriod()->getEndDateTime());

        $this->assertEquals($outputContent, $this->task->getOutput()->getOutput());
        $this->assertEquals($stateName, $this->task->getState()->getName());
        $this->assertNull($this->task->getWorker());
        $this->assertNull($this->task->getRemoteId());
    }

    /**
     * @return array
     */
    public function completeSuccessDataProvider()
    {
        return [
            'no worker, no remote id, no time period' => [
                'taskValues' => [],
                'endDateTime' => new \DateTime('2010-01-01 12:00:00'),
                'outputContent' => 'foo',
                'stateName' => TaskService::COMPLETED_STATE,
            ],
            'has worker, has remote id, has time period' => [
                'taskValues' => [
                    TaskFactory::KEY_WORKER => 'worker.simplytestable.com',
                    TaskFactory::KEY_REMOTE_ID => 1,
                    TaskFactory::KEY_TIME_PERIOD => [
                        TimePeriodFactory::KEY_START_DATE_TIME => new \DateTime(),
                    ],
                ],
                'endDateTime' => new \DateTime('2010-01-01 12:00:00'),
                'outputContent' => 'foo',
                'stateName' => TaskService::COMPLETED_STATE,
            ],
        ];
    }

    public function testGetAvailableStateNames()
    {
        $this->assertEquals([
            TaskService::CANCELLED_STATE,
            TaskService::QUEUED_STATE,
            TaskService::IN_PROGRESS_STATE,
            TaskService::COMPLETED_STATE,
            TaskService::AWAITING_CANCELLATION_STATE,
            TaskService::QUEUED_FOR_ASSIGNMENT_STATE,
            TaskService::TASK_FAILED_NO_RETRY_AVAILABLE_STATE,
            TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
            TaskService::TASK_FAILED_RETRY_LIMIT_REACHED_STATE,
            TaskService::TASK_SKIPPED_STATE
        ], $this->taskService->getAvailableStateNames());
    }

    public function testGetEntityRepository()
    {
        $this->assertInstanceOf(TaskRepository::class, $this->taskService->getEntityRepository());
    }
}
