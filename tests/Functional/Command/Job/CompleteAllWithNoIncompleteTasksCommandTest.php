<?php

namespace App\Tests\Functional\Command\Job;

use App\Command\Job\CompleteAllWithNoIncompleteTasksCommand;
use App\Entity\Job\Job;
use App\Entity\Task\Task;
use App\Services\JobTypeService;
use App\Tests\Services\ObjectReflector;
use App\Tests\Services\TaskFactory;
use App\Tests\Services\UserFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\JobFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CompleteAllWithNoIncompleteTasksCommandTest extends AbstractBaseTestCase
{
    /**
     * @var CompleteAllWithNoIncompleteTasksCommand
     */
    private $command;

    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = self::$container->get(CompleteAllWithNoIncompleteTasksCommand::class);
        $this->jobFactory = self::$container->get(JobFactory::class);
        $this->userFactory = self::$container->get(UserFactory::class);
    }

    /**
     * @dataProvider runDataProvider
     *
     * @param array $jobValuesCollection
     * @param int[] $crawlJobIndices
     * @param array $commandInput
     * @param int $expectedReturnCode
     * @param string[] $expectedJobStateNames
     */
    public function testRun(
        $jobValuesCollection,
        $crawlJobIndices,
        $commandInput,
        $expectedReturnCode,
        $expectedJobStateNames
    ) {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $jobTypeService = self::$container->get(JobTypeService::class);

        $users = $this->userFactory->createPublicAndPrivateUserSet();

        foreach ($jobValuesCollection as $jobValuesIndex => $jobValues) {
            if (isset($jobValues[JobFactory::KEY_USER])) {
                $jobValues[JobFactory::KEY_USER] = $users[$jobValues[JobFactory::KEY_USER]];
                $jobValuesCollection[$jobValuesIndex] = $jobValues;
            }
        }

        $jobs = $this->jobFactory->createResolveAndPrepareCollection($jobValuesCollection);

        $crawlJobType = $jobTypeService->getCrawlType();

        foreach ($jobs as $jobIndex => $job) {
            if (in_array($jobIndex, $crawlJobIndices)) {
                ObjectReflector::setProperty($job, Job::class, 'type', $crawlJobType);

                $entityManager->persist($job);
                $entityManager->flush();
            }
        }

        $returnCode = $this->command->run(new ArrayInput($commandInput), new BufferedOutput());

        $this->assertEquals($expectedReturnCode, $returnCode);

        foreach ($jobs as $jobIndex => $job) {
            $expectedStateName = $expectedJobStateNames[$jobIndex];
            $this->assertEquals($expectedStateName, $job->getState()->getName());
        }
    }

    /**
     * @return array
     */
    public function runDataProvider()
    {
        return [
            'no jobs' => [
                'jobValuesCollection' => [],
                'crawlJobIndices' => [],
                'commandInput' => [],
                'expectedReturnCode' => CompleteAllWithNoIncompleteTasksCommand::RETURN_CODE_NO_MATCHING_JOBS,
                'expectedJobStateNames' => [],
            ],
            'jobs with only incomplete tasks' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TASKS => [
                            [
                                TaskFactory::KEY_STATE => Task::STATE_QUEUED_FOR_ASSIGNMENT,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_IN_PROGRESS,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TASKS => [
                            [
                                TaskFactory::KEY_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_IN_PROGRESS,
                            ],
                        ],
                    ],
                ],
                'crawlJobIndices' => [],
                'commandInput' => [],
                'expectedReturnCode' => CompleteAllWithNoIncompleteTasksCommand::RETURN_CODE_NO_MATCHING_JOBS,
                'expectedJobStateNames' => [
                    Job::STATE_QUEUED,
                    Job::STATE_QUEUED,
                ],
            ],
            'jobs with all mixed complete and incomplete tasks' => [
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
                                TaskFactory::KEY_STATE => Task::STATE_CANCELLED,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TASKS => [
                            [
                                TaskFactory::KEY_STATE => Task::STATE_SKIPPED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_IN_PROGRESS,
                            ],
                        ],
                    ],
                ],
                'crawlJobIndices' => [],
                'commandInput' => [],
                'expectedReturnCode' => CompleteAllWithNoIncompleteTasksCommand::RETURN_CODE_NO_MATCHING_JOBS,
                'expectedJobStateNames' => [
                    Job::STATE_QUEUED,
                    Job::STATE_QUEUED,
                ],
            ],
            'jobs with some all-complete tasks' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TASKS => [
                            [
                                TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TASKS => [
                            [
                                TaskFactory::KEY_STATE => Task::STATE_SKIPPED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_IN_PROGRESS,
                            ],
                        ],
                    ],
                ],
                'crawlJobIndices' => [],
                'commandInput' => [],
                'expectedReturnCode' => CompleteAllWithNoIncompleteTasksCommand::RETURN_CODE_OK,
                'expectedJobStateNames' => [
                    Job::STATE_COMPLETED,
                    Job::STATE_QUEUED,
                ],
            ],
            'jobs with some all-complete tasks, dry-run' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TASKS => [
                            [
                                TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TASKS => [
                            [
                                TaskFactory::KEY_STATE => Task::STATE_SKIPPED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_IN_PROGRESS,
                            ],
                        ],
                    ],
                ],
                'crawlJobIndices' => [],
                'commandInput' => [
                    '--dry-run' => true,
                ],
                'expectedReturnCode' => CompleteAllWithNoIncompleteTasksCommand::RETURN_CODE_OK,
                'expectedJobStateNames' => [
                    Job::STATE_QUEUED,
                    Job::STATE_QUEUED,
                ],
            ],
            'jobs with some all-complete tasks and with crawl jobs' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TASKS => [
                            [
                                TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_CANCELLED,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_URL => 'http://foo.example.com',
                        JobFactory::KEY_DOMAIN => 'foo.example.com',
                        JobFactory::KEY_TASKS => [
                            [
                                TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_CANCELLED,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_URL => 'http://bar.example.com',
                        JobFactory::KEY_DOMAIN => 'bar.example.com',
                        JobFactory::KEY_TASKS => [
                            [
                                TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_CANCELLED,
                            ],
                        ],
                    ],
                ],
                'crawlJobIndices' => [1],
                'commandInput' => [],
                'expectedReturnCode' => CompleteAllWithNoIncompleteTasksCommand::RETURN_CODE_OK,
                'expectedJobStateNames' => [
                    Job::STATE_COMPLETED,
                    Job::STATE_QUEUED,
                    Job::STATE_COMPLETED,
                ],
            ],
        ];
    }
}
