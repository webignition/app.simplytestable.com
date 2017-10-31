<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\TimePeriod;
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
}
