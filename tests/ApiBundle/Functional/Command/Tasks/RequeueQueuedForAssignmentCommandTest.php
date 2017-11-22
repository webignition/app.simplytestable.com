<?php

namespace Tests\ApiBundle\Functional\Command\Tasks;

use SimplyTestable\ApiBundle\Command\Tasks\RequeueQueuedForAssignmentCommand;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\TaskService;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class RequeueQueuedForAssignmentCommandTest extends AbstractBaseTestCase
{
    /**
     * @var RequeueQueuedForAssignmentCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = $this->container->get(RequeueQueuedForAssignmentCommand::class);
    }

    /**
     * @dataProvider runSuccessDataProvider
     *
     * @param array $jobValuesCollection
     * @param string $expectedTaskStateNames
     */
    public function testRunSuccess($jobValuesCollection, $expectedTaskStateNames)
    {
        $userFactory = new UserFactory($this->container);
        $users = $userFactory->createPublicAndPrivateUserSet();

        foreach ($jobValuesCollection as $jobValuesIndex => $jobValues) {
            if (isset($jobValues[JobFactory::KEY_USER])) {
                $jobValues[JobFactory::KEY_USER] = $users[$jobValues[JobFactory::KEY_USER]];
                $jobValuesCollection[$jobValuesIndex] = $jobValues;
            }
        }

        $jobFactory = new JobFactory($this->container);
        $jobs = $jobFactory->createResolveAndPrepareCollection($jobValuesCollection);

        /* @var Task[] $tasks */
        $tasks = [];

        foreach ($jobs as $job) {
            $tasks = array_merge($tasks, $job->getTasks()->toArray());
        }

        $returnCode = $this->command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(
            RequeueQueuedForAssignmentCommand::RETURN_CODE_OK,
            $returnCode
        );

        foreach ($tasks as $taskIndex => $task) {
            $expectedStateName = $expectedTaskStateNames[$taskIndex];

            $this->assertEquals($expectedStateName, $task->getState()->getName());
        }
    }

    /**
     * @return array
     */
    public function runSuccessDataProvider()
    {
        return [
            'no tasks' => [
                'jobValuesCollection' => [],
                'expectedTaskStateNames' => [],
            ],
            'all tasks already queued' => [
                'jobValuesCollection' => [
                    [],
                ],
                'expectedTaskStateNames' => [
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                ],
            ],
            'mixed states' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::QUEUED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::QUEUED_FOR_ASSIGNMENT_STATE,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::CANCELLED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::QUEUED_FOR_ASSIGNMENT_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
                            ],
                        ],
                    ],
                ],
                'expectedTaskStateNames' => [
                    TaskService::COMPLETED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
                ],
            ],
        ];
    }
}
