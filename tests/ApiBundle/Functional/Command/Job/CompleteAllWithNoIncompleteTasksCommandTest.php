<?php

namespace Tests\ApiBundle\Functional\Command\Job;

use SimplyTestable\ApiBundle\Command\Job\CompleteAllWithNoIncompleteTasksCommand;
use SimplyTestable\ApiBundle\Controller\MaintenanceController;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\TaskService;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Tests\ApiBundle\Factory\JobFactory;
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

        $this->command = $this->container->get('simplytestable.command.job.completealwithnoincompletetasks');
        $this->jobFactory = new JobFactory($this->container);
        $this->userFactory = new UserFactory($this->container);
    }

    public function testRunInMaintenanceReadOnlyMode()
    {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->setContainer($this->container);
        $maintenanceController->enableReadOnlyAction();

        $returnCode = $this->command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(
            CompleteAllWithNoIncompleteTasksCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );

        $maintenanceController->disableReadOnlyAction();
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
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $jobTypeService = $this->container->get(JobTypeService::class);

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
                $job->setType($crawlJobType);
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
                                JobFactory::KEY_TASK_STATE => TaskService::QUEUED_FOR_ASSIGNMENT_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::QUEUED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::IN_PROGRESS_STATE,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::QUEUED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::QUEUED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::IN_PROGRESS_STATE,
                            ],
                        ],
                    ],
                ],
                'crawlJobIndices' => [],
                'commandInput' => [],
                'expectedReturnCode' => CompleteAllWithNoIncompleteTasksCommand::RETURN_CODE_NO_MATCHING_JOBS,
                'expectedJobStateNames' => [
                    JobService::QUEUED_STATE,
                    JobService::QUEUED_STATE,
                ],
            ],
            'jobs with all mixed complete and incomplete tasks' => [
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
                                JobFactory::KEY_TASK_STATE => TaskService::CANCELLED_STATE,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::TASK_SKIPPED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::QUEUED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::IN_PROGRESS_STATE,
                            ],
                        ],
                    ],
                ],
                'crawlJobIndices' => [],
                'commandInput' => [],
                'expectedReturnCode' => CompleteAllWithNoIncompleteTasksCommand::RETURN_CODE_NO_MATCHING_JOBS,
                'expectedJobStateNames' => [
                    JobService::QUEUED_STATE,
                    JobService::QUEUED_STATE,
                ],
            ],
            'jobs with some all-complete tasks' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::TASK_SKIPPED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::QUEUED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::IN_PROGRESS_STATE,
                            ],
                        ],
                    ],
                ],
                'crawlJobIndices' => [],
                'commandInput' => [],
                'expectedReturnCode' => CompleteAllWithNoIncompleteTasksCommand::RETURN_CODE_OK,
                'expectedJobStateNames' => [
                    JobService::COMPLETED_STATE,
                    JobService::QUEUED_STATE,
                ],
            ],
            'jobs with some all-complete tasks, dry-run' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::TASK_SKIPPED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::QUEUED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::IN_PROGRESS_STATE,
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
                    JobService::QUEUED_STATE,
                    JobService::QUEUED_STATE,
                ],
            ],
            'jobs with some all-complete tasks and with crawl jobs' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::CANCELLED_STATE,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_SITE_ROOT_URL => 'http://foo.example.com',
                        JobFactory::KEY_DOMAIN => 'foo.example.com',
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::CANCELLED_STATE,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_SITE_ROOT_URL => 'http://bar.example.com',
                        JobFactory::KEY_DOMAIN => 'bar.example.com',
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::CANCELLED_STATE,
                            ],
                        ],
                    ],
                ],
                'crawlJobIndices' => [1],
                'commandInput' => [],
                'expectedReturnCode' => CompleteAllWithNoIncompleteTasksCommand::RETURN_CODE_OK,
                'expectedJobStateNames' => [
                    JobService::COMPLETED_STATE,
                    JobService::QUEUED_STATE,
                    JobService::COMPLETED_STATE,
                ],
            ],
        ];
    }
}
