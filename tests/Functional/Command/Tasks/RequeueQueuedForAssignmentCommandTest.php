<?php

namespace App\Tests\Functional\Command\Tasks;

use App\Command\Tasks\RequeueQueuedForAssignmentCommand;
use App\Entity\Task\Task;
use App\Tests\Services\TaskFactory;
use App\Tests\Services\UserFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\JobFactory;
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

        $this->command = self::$container->get(RequeueQueuedForAssignmentCommand::class);
    }

    /**
     * @dataProvider runSuccessDataProvider
     *
     * @param array $jobValuesCollection
     * @param string $expectedTaskStateNames
     */
    public function testRunSuccess($jobValuesCollection, $expectedTaskStateNames)
    {
        $userFactory = self::$container->get(UserFactory::class);
        $users = $userFactory->createPublicAndPrivateUserSet();

        foreach ($jobValuesCollection as $jobValuesIndex => $jobValues) {
            if (isset($jobValues[JobFactory::KEY_USER])) {
                $jobValues[JobFactory::KEY_USER] = $users[$jobValues[JobFactory::KEY_USER]];
                $jobValuesCollection[$jobValuesIndex] = $jobValues;
            }
        }

        $jobFactory = self::$container->get(JobFactory::class);
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

            $this->assertEquals($expectedStateName, (string) $task->getState());
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
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                ],
            ],
            'mixed states' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TASKS => [
                            [
                                TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_QUEUED_FOR_ASSIGNMENT,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TASKS => [
                            [
                                TaskFactory::KEY_STATE => Task::STATE_CANCELLED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_QUEUED_FOR_ASSIGNMENT,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_FAILED_RETRY_AVAILABLE,
                            ],
                        ],
                    ],
                ],
                'expectedTaskStateNames' => [
                    Task::STATE_COMPLETED,
                    Task::STATE_QUEUED,
                    Task::STATE_QUEUED,
                    Task::STATE_CANCELLED,
                    Task::STATE_QUEUED,
                    Task::STATE_FAILED_RETRY_AVAILABLE,
                ],
            ],
        ];
    }
}
