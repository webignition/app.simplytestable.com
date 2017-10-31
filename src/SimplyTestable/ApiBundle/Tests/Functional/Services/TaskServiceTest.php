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
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->taskService = $this->container->get('simplytestable.services.taskservice');

        $jobFactory = new JobFactory($this->container);
        $this->job = $jobFactory->createResolveAndPrepare();
    }

    public function testCancelFinishedTask()
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');

        /* @var Task $task */
        $task = $this->job->getTasks()->get(0);

        $finishedStateNames = $this->taskService->getFinishedStateNames();

        foreach ($finishedStateNames as $stateName) {
            $task->setState($stateService->fetch($stateName));
            $this->taskService->cancel($task);
            $this->assertEquals($stateName, $task->getState()->getName());
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
        /* @var Task $task */
        $task = $this->job->getTasks()->get(0);

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
        $taskFactory->update($task, $taskValues);

        $this->taskService->cancel($task);

        $this->assertEquals(TaskService::CANCELLED_STATE, $task->getState()->getName());
        $this->assertNull($task->getWorker());
        $this->assertInstanceOf(TimePeriod::class, $task->getTimePeriod());
        $this->assertInstanceOf(\DateTime::class, $task->getTimePeriod()->getStartDateTime());
        $this->assertInstanceOf(\DateTime::class, $task->getTimePeriod()->getEndDateTime());
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
}
