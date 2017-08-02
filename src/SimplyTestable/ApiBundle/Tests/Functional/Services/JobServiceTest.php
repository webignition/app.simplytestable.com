<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services;

use SimplyTestable\ApiBundle\Entity\Job\Ammendment;
use SimplyTestable\ApiBundle\Entity\Job\Configuration;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class JobServiceTest extends BaseSimplyTestableTestCase
{
    /**
     * @var JobService
     */
    private $jobService;

    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobService = $this->container->get('simplytestable.services.jobservice');
        $this->jobFactory = new JobFactory($this->container);
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param string $userEmail
     * @param string $url
     * @param string $jobTypeName
     * @param array $jobParameters
     * @param array $taskConfigurationCollectionValues
     * @param string $expectedUserEmail
     * @param string $expectedWebsiteUrl
     * @param string $expectedJobTypeName
     * @param array $expectedJobParameters
     * @param array $expectedJobTaskTypes
     * @param array $expectedJobTaskTypeOptions
     */
    public function testCreate(
        $userEmail,
        $url,
        $jobTypeName,
        $jobParameters,
        $taskConfigurationCollectionValues,
        $expectedUserEmail,
        $expectedWebsiteUrl,
        $expectedJobTypeName,
        $expectedJobParameters,
        $expectedJobTaskTypes,
        $expectedJobTaskTypeOptions
    ) {
        $userFactory = new UserFactory($this->container);
        $websiteService = $this->container->get('simplytestable.services.websiteservice');
        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');
        $stateService = $this->container->get('simplytestable.services.stateservice');

        $user = $userFactory->create($userEmail);
        $website = $websiteService->fetch($url);
        $jobType = $jobTypeService->getByName($jobTypeName);

        $jobConfiguration = new Configuration();
        $jobConfiguration->setUser($user);
        $jobConfiguration->setWebsite($website);
        $jobConfiguration->setType($jobType);
        $jobConfiguration->setParameters(json_encode($jobParameters));

        foreach ($taskConfigurationCollectionValues as $taskConfigurationValues) {
            $taskType = $taskTypeService->getByName($taskConfigurationValues['type']);

            $taskConfiguration = new TaskConfiguration();
            $taskConfiguration->setType($taskType);
            $taskConfiguration->setIsEnabled($taskConfigurationValues['isEnabled']);
            $taskConfiguration->setOptions($taskConfigurationValues['options']);

            $jobConfiguration->addTaskConfiguration($taskConfiguration);
        }

        $job = $this->jobService->create($jobConfiguration);

        $this->assertInstanceOf(Job::class, $job);

        $jobStartingState = $stateService->fetch(JobService::STARTING_STATE);

        $this->assertEquals($expectedUserEmail, $job->getUser()->getEmail());
        $this->assertEquals($expectedWebsiteUrl, $job->getWebsite()->getCanonicalUrl());
        $this->assertEquals($expectedJobTypeName, $job->getType()->getName());
        $this->assertEquals($expectedJobParameters, $job->getParametersArray());
        $this->assertEquals($jobStartingState, $job->getState());

        $jobTaskTypes = $job->getTaskTypeCollection()->get();

        $this->assertCount(count($expectedJobTaskTypes), $jobTaskTypes);

        foreach ($jobTaskTypes as $jobTaskTypeIndex => $jobTaskType) {
            $expectedJobTaskType = $expectedJobTaskTypes[$jobTaskTypeIndex];

            $this->assertEquals($expectedJobTaskType, $jobTaskType->getName());
        }

        $jobTaskTypeOptions = $job->getTaskTypeOptions();

        $this->assertCount(count($expectedJobTaskTypeOptions), $jobTaskTypeOptions);

        foreach ($jobTaskTypeOptions as $taskTypeOptionIndex => $taskTypeOptions) {
            /* @var TaskTypeOptions $taskTypeOptions */
            $expectedTaskTypeOptions = $expectedJobTaskTypeOptions[$taskTypeOptionIndex];

            $this->assertEquals($job, $taskTypeOptions->getJob());
            $this->assertEquals($expectedTaskTypeOptions['taskType'], $taskTypeOptions->getTaskType()->getName());
            $this->assertEquals($expectedTaskTypeOptions['options'], $taskTypeOptions->getOptions());
        }
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'public user, full site, html validation only' => [
                'userEmail' => 'public@simplytestable.com',
                'url' => 'http://example.com',
                'jobTypeName' => JobTypeService::FULL_SITE_NAME,
                'jobParameters' => null,
                'taskConfigurationCollectionValues' => [
                    [
                        'type' => 'HTML validation',
                        'isEnabled' => true,
                        'options' => [
                            'foo' => 'bar',
                        ],
                    ],
                ],
                'expectedUserEmail' => 'public@simplytestable.com',
                'expectedWebsiteUrl' => 'http://example.com/',
                'expectedJobTypeName' => 'Full site',
                'expectedJobParameters' => null,
                'expectedJobTaskTypes' => [
                    'HTML validation',
                ],
                'expectedJobTaskTypeOptions' => [
                    [
                        'taskType' => 'HTML validation',
                        'options' => [
                            'foo' => 'bar',
                        ],
                    ],
                ],
            ],
            'public user, single url, parameters, html validation only' => [
                'userEmail' => 'public@simplytestable.com',
                'url' => 'http://example.com/foo',
                'jobTypeName' => JobTypeService::SINGLE_URL_NAME,
                'jobParameters' => [
                    'param-foo' => 'param-bar',
                ],
                'taskConfigurationCollectionValues' => [
                    [
                        'type' => 'HTML validation',
                        'isEnabled' => true,
                        'options' => [
                            'html-foo' => 'html-bar',
                        ],
                    ],
                    [
                        'type' => 'CSS validation',
                        'isEnabled' => false,
                        'options' => [
                            'css-foo' => 'css-bar',
                        ],
                    ],
                ],
                'expectedUserEmail' => 'public@simplytestable.com',
                'expectedWebsiteUrl' => 'http://example.com/foo',
                'expectedJobTypeName' => 'Single URL',
                'expectedJobParameters' => [
                    'param-foo' => 'param-bar',
                ],
                'expectedJobTaskTypes' => [
                    'HTML validation',
                ],
                'expectedJobTaskTypeOptions' => [
                    [
                        'taskType' => 'HTML validation',
                        'options' => [
                            'html-foo' => 'html-bar',
                        ],
                    ],
                ],
            ],
            'private user, full site, parameters, all task types' => [
                'userEmail' => 'user@example.com',
                'url' => 'http://foo.example.com',
                'jobTypeName' => JobTypeService::FULL_SITE_NAME,
                'jobParameters' => [
                    'param-foo-0' => 'param-bar-0',
                    'param-foo-1' => 'param-bar-1',
                ],
                'taskConfigurationCollectionValues' => [
                    [
                        'type' => 'HTML validation',
                        'isEnabled' => true,
                        'options' => [
                            'html-foo' => 'html-bar',
                        ],
                    ],
                    [
                        'type' => 'CSS validation',
                        'isEnabled' => true,
                        'options' => [
                            'css-foo' => 'css-bar',
                        ],
                    ],
                    [
                        'type' => 'JS static analysis',
                        'isEnabled' => true,
                        'options' => [
                            'js-foo' => 'js-bar',
                        ],
                    ],
                    [
                        'type' => 'Link integrity',
                        'isEnabled' => true,
                        'options' => [
                            'li-foo' => 'li-bar',
                        ],
                    ],
                ],
                'expectedUserEmail' => 'user@example.com',
                'expectedWebsiteUrl' => 'http://foo.example.com/',
                'expectedJobTypeName' => 'Full site',
                'expectedJobParameters' => [
                    'param-foo-0' => 'param-bar-0',
                    'param-foo-1' => 'param-bar-1',
                ],
                'expectedJobTaskTypes' => [
                    'HTML validation',
                    'CSS validation',
                    'JS static analysis',
                    'Link integrity',
                ],
                'expectedJobTaskTypeOptions' => [
                    [
                        'taskType' => 'HTML validation',
                        'options' => [
                            'html-foo' => 'html-bar',
                        ],
                    ],
                    [
                        'taskType' => 'CSS validation',
                        'options' => [
                            'css-foo' => 'css-bar',
                        ],
                    ],
                    [
                        'taskType' => 'JS static analysis',
                        'options' => [
                            'js-foo' => 'js-bar',
                        ],
                    ],
                    [
                        'taskType' => 'Link integrity',
                        'options' => [
                            'li-foo' => 'li-bar',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testGetJobById()
    {
        $jobs[] = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => 'http://foo.example.com',
        ]);

        $jobs[] = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => 'http://bar.example.com',
        ]);

        $this->assertEquals($jobs[0], $this->jobService->getById($jobs[0]->getId()));
        $this->assertEquals($jobs[1], $this->jobService->getById($jobs[1]->getId()));
    }

    /**
     * @dataProvider isStateDataProvider
     *
     * @param string $stateName
     * @param bool $expectedIsFinished
     * @param bool $expectedIsNew
     * @param bool $expectedIsPreparing
     */
    public function testIsState($stateName, $expectedIsFinished, $expectedIsNew, $expectedIsPreparing)
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $state = $stateService->fetch($stateName);

        $job = $this->jobFactory->create();
        $job->setState($state);
        $this->jobService->persistAndFlush($job);

        $this->assertEquals($expectedIsFinished, $this->jobService->isFinished($job));
        $this->assertEquals($expectedIsNew, JobService::STARTING_STATE == $job->getState());
        $this->assertEquals($expectedIsPreparing, JobService::PREPARING_STATE == $job->getState());
    }

    /**
     * @return array
     */
    public function isStateDataProvider()
    {
        return [
            JobService::STARTING_STATE => [
                'stateName' => JobService::STARTING_STATE,
                'expectedIsFinished' => false,
                'expectedIsNew' => true,
                'expectedIsPreparing' => false,
            ],
            JobService::CANCELLED_STATE => [
                'stateName' => JobService::CANCELLED_STATE,
                'expectedIsFinished' => true,
                'expectedIsNew' => false,
                'expectedIsPreparing' => false,
            ],
            JobService::COMPLETED_STATE => [
                'stateName' => JobService::COMPLETED_STATE,
                'expectedIsFinished' => true,
                'expectedIsNew' => false,
                'expectedIsPreparing' => false,
            ],
            JobService::IN_PROGRESS_STATE => [
                'stateName' => JobService::IN_PROGRESS_STATE,
                'expectedIsFinished' => false,
                'expectedIsNew' => false,
                'expectedIsPreparing' => false,
            ],
            JobService::PREPARING_STATE => [
                'stateName' => JobService::PREPARING_STATE,
                'expectedIsFinished' => false,
                'expectedIsNew' => false,
                'expectedIsPreparing' => true,
            ],
            JobService::QUEUED_STATE => [
                'stateName' => JobService::QUEUED_STATE,
                'expectedIsFinished' => false,
                'expectedIsNew' => false,
                'expectedIsPreparing' => false,
            ],
            JobService::FAILED_NO_SITEMAP_STATE => [
                'stateName' => JobService::FAILED_NO_SITEMAP_STATE,
                'expectedIsFinished' => true,
                'expectedIsNew' => false,
                'expectedIsPreparing' => false,
            ],
            JobService::REJECTED_STATE => [
                'stateName' => JobService::REJECTED_STATE,
                'expectedIsFinished' => true,
                'expectedIsNew' => false,
                'expectedIsPreparing' => false,
            ],
            JobService::RESOLVING_STATE => [
                'stateName' => JobService::RESOLVING_STATE,
                'expectedIsFinished' => false,
                'expectedIsNew' => false,
                'expectedIsPreparing' => false,
            ],
            JobService::RESOLVED_STATE => [
                'stateName' => JobService::RESOLVED_STATE,
                'expectedIsFinished' => false,
                'expectedIsNew' => false,
                'expectedIsPreparing' => false,
            ],
        ];
    }

    /**
     * @dataProvider cancelFinishedJobDataProvider
     *
     * @param string $stateName
     */
    public function testCancelFinishedJob($stateName)
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');

        $job = $this->jobFactory->create();
        $job->setState($stateService->fetch($stateName));

        $this->jobService->persistAndFlush($job);

        $this->jobService->cancel($job);

        $this->assertEquals($stateName, $job->getState());
    }

    /**
     * @return array
     */
    public function cancelFinishedJobDataProvider()
    {
        return [
            JobService::REJECTED_STATE => [
                'stateName' => JobService::REJECTED_STATE,
            ],
            JobService::CANCELLED_STATE => [
                'stateName' => JobService::CANCELLED_STATE,
            ],
            JobService::COMPLETED_STATE => [
                'stateName' => JobService::COMPLETED_STATE,
            ],
        ];
    }

    /**
     * @dataProvider cancelDataProvider
     *
     * @param array $jobValues
     * @param bool $resolveAndPrepare
     */
    public function testCancel($jobValues, $resolveAndPrepare)
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $taskService = $this->container->get('simplytestable.services.taskservice');

        if ($resolveAndPrepare) {
            $job = $this->jobFactory->createResolveAndPrepare($jobValues);
        } else {
            $job = $this->jobFactory->create();
        }

        $tasks = $job->getTasks();

        if (count($tasks)) {
            /* @var Task $task */
            $task = $tasks->first();

            $task->setState($stateService->fetch(TaskService::IN_PROGRESS_STATE));
            $taskService->persistAndFlush($task);
        }

        $this->jobService->cancel($job);

        $this->assertEquals(JobService::CANCELLED_STATE, $job->getState());

        foreach ($tasks as $taskIndex => $task) {
            if ($taskIndex === 0) {
                $this->assertEquals(TaskService::AWAITING_CANCELLATION_STATE, $task->getState());
            } else {
                $this->assertEquals(TaskService::CANCELLED_STATE, $task->getState());
            }
        }
    }

    /**
     * @return array
     */
    public function cancelDataProvider()
    {
        return [
            'no tasks' => [
                'jobValues' => [],
                'resolveAndPrepare' => false,
            ],
            'with tasks' => [
                'jobValues' => [],
                'resolveAndPrepare' => true,
            ],
        ];
    }

    /**
     * @dataProvider addAmmendmentDataProvider
     *
     * @param string $user
     * @param array $jobValues
     * @param string $reason
     * @param string $constraintName
     * @param string $expectedReason
     * @param string $expectedConstraintName
     */
    public function testAddAmmendment(
        $user,
        $jobValues,
        $reason,
        $constraintName,
        $expectedReason,
        $expectedConstraintName
    ) {
        $userFactory = new UserFactory($this->container);
        $users = $userFactory->createPublicAndPrivateUserSet();

        $jobValues[JobFactory::KEY_USER] = $users[$user];

        $job = $this->jobFactory->create($jobValues);

        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');
        $userAccountPlan = $userAccountPlanService->getForUser($job->getUser());

        $constraint = empty($constraintName)
            ? null
            : $userAccountPlan->getPlan()->getConstraintNamed($constraintName);

        $this->jobService->addAmmendment($job, $reason, $constraint);

        /* @var Ammendment[] $ammendments */
        $ammendments = $job->getAmmendments();

        $this->assertCount(1, $ammendments);

        $ammendment = $ammendments[0];

        $this->assertEquals($job, $ammendment->getJob());
        $this->assertEquals($expectedReason, $ammendment->getReason());

        if (empty($expectedConstraintName)) {
            $this->assertNull($ammendment->getConstraint());
        } else {
            $this->assertEquals($expectedConstraintName, $ammendment->getConstraint()->getName());
        }
    }

    /**
     * @return array
     */
    public function addAmmendmentDataProvider()
    {
        return [
            'public user, no constraint' => [
                'user' => 'public',
                'jobValues' => [],
                'reason' => 'foo',
                'constraintName' => null,
                'expectedReason' => 'foo',
                'expectedConstraintName' => null,
            ],
            'public user, foo' => [
                'user' => 'public',
                'jobValues' => [],
                'reason' => 'foo',
                'constraintName' => 'full_site_jobs_per_site',
                'expectedReason' => 'foo',
                'expectedConstraintName' => 'full_site_jobs_per_site',
            ],
        ];
    }

    public function testCancelIncompleteTasks()
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $taskService = $this->container->get('simplytestable.services.taskservice');

        $finishedTaskStates = [
            TaskService::CANCELLED_STATE,
            TaskService::COMPLETED_STATE,
            TaskService::TASK_FAILED_NO_RETRY_AVAILABLE_STATE,
            TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
            TaskService::TASK_FAILED_RETRY_LIMIT_REACHED_STATE,
            TaskService::TASK_SKIPPED_STATE,
        ];

        $incompleteTaskStates = [
            TaskService::QUEUED_STATE,
            TaskService::IN_PROGRESS_STATE,
            TaskService::AWAITING_CANCELLATION_STATE,
            TaskService::QUEUED_FOR_ASSIGNMENT_STATE,
        ];

        $job = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_TEST_TYPES => [
                'html validation',
                'css validation',
                'js static analysis',
                'link integrity',
            ],
        ]);

        /* @var Task[] $tasks */
        $tasks = $job->getTasks()->toArray();

        /* @var Task[] $tasksToRemainUnchanged */
        $tasksToRemainUnchanged = array_slice($tasks, 0, count($finishedTaskStates));

        /* @var Task[] $tasksToChange */
        $tasksToChange = array_slice($tasks, count($finishedTaskStates));

        foreach ($finishedTaskStates as $stateIndex => $stateName) {
            $task = $tasksToRemainUnchanged[$stateIndex];
            $state = $stateService->fetch($stateName);

            $task->setState($state);
            $taskService->persistAndFlush($task);
        }

        foreach ($incompleteTaskStates as $stateIndex => $stateName) {
            $task = $tasksToChange[$stateIndex];
            $state = $stateService->fetch($stateName);

            $task->setState($state);
            $taskService->persistAndFlush($task);
        }

        $this->jobService->cancelIncompleteTasks($job);

        foreach ($tasksToRemainUnchanged as $taskIndex => $task) {
            $expectedStateName = $finishedTaskStates[$taskIndex];

            $this->assertEquals($expectedStateName, $task->getState()->getName());
        }

        foreach ($tasksToChange as $taskIndex => $task) {
            $this->assertEquals(TaskService::CANCELLED_STATE, $task->getState()->getName());
        }
    }

    /**
     * @dataProvider completeFinishedJobDataProvider
     *
     * @param string $stateName
     */
    public function testCompleteFinishedJob($stateName)
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');

        $job = $this->jobFactory->create();

        $state = $stateService->fetch($stateName);
        $job->setState($state);

        $this->jobService->persistAndFlush($job);

        $this->jobService->complete($job);

        $this->assertEquals($stateName, $job->getState()->getName());
    }

    /**
     * @return array
     */
    public function completeFinishedJobDataProvider()
    {
        return [
            'finished; rejected' => [
                'state' => JobService::REJECTED_STATE,
            ],
            'finished; cancelled' => [
                'state' => JobService::CANCELLED_STATE,
            ],
            'finished; completed' => [
                'state' => JobService::COMPLETED_STATE,
            ],
            'finished; failed-no-sitemap' => [
                'state' => JobService::FAILED_NO_SITEMAP_STATE,
            ],
        ];
    }

    public function testCompleteWithIncompleteTasks()
    {
        $job = $this->jobFactory->createResolveAndPrepare();
        $this->assertEquals(JobService::QUEUED_STATE, $job->getState()->getName());

        $this->jobService->complete($job);

        $this->assertEquals(JobService::QUEUED_STATE, $job->getState()->getName());
    }

    public function testComplete()
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');

        $job = $this->jobFactory->createResolveAndPrepare();
        $this->assertEquals(JobService::QUEUED_STATE, $job->getState()->getName());
        $this->assertNull($job->getTimePeriod()->getEndDateTime());

        $this->jobFactory->setTaskStates($job, $stateService->fetch(TaskService::COMPLETED_STATE));

        $this->jobService->complete($job);

        $this->assertEquals(JobService::COMPLETED_STATE, $job->getState()->getName());
        $this->assertInstanceOf(\DateTime::class, $job->getTimePeriod()->getEndDateTime());
    }

    public function testGetUnfinishedJobsWithTasksAndNoIncompleteTasks()
    {
        $stateNames = [
            JobService::STARTING_STATE,
            JobService::CANCELLED_STATE,
            JobService::COMPLETED_STATE,
            JobService::IN_PROGRESS_STATE,
            JobService::PREPARING_STATE,
            JobService::QUEUED_STATE,
            JobService::FAILED_NO_SITEMAP_STATE,
            JobService::REJECTED_STATE,
            JobService::RESOLVING_STATE,
            JobService::RESOLVED_STATE,
        ];
        $zeroTaskStates = [
            JobService::STARTING_STATE,
            JobService::RESOLVING_STATE,
            JobService::RESOLVED_STATE,
        ];

        $completedTasksStates = [
            JobService::IN_PROGRESS_STATE,
            JobService::PREPARING_STATE,
            JobService::QUEUED_STATE,
        ];

        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create();

        $stateService = $this->container->get('simplytestable.services.stateservice');

        /* @var Job[] $jobs */
        $jobs = [];

        foreach ($stateNames as $stateName) {
            $domain = $stateName . '.example.com';

            if (in_array($stateName, $zeroTaskStates)) {
                $job = $this->jobFactory->create([
                        JobFactory::KEY_USER => $user,
                        JobFactory::KEY_SITE_ROOT_URL => 'http://' . $domain,
                ]);
            } else {
                $job = $this->jobFactory->createResolveAndPrepare(
                    [
                        JobFactory::KEY_USER => $user,
                        JobFactory::KEY_SITE_ROOT_URL => 'http://' . $domain,
                    ],
                    [],
                    $domain
                );
            }

            $job->setState($stateService->fetch($stateName));

            $jobs[$stateName] = $job;
        }

        $taskCompletedState = $stateService->fetch(TaskService::COMPLETED_STATE);

        foreach ($completedTasksStates as $stateName) {
            $job = $jobs[$stateName];
            $this->jobFactory->setTaskStates($job, $taskCompletedState);
        }

        $retrievedJobs = $this->jobService->getUnfinishedJobsWithTasksAndNoIncompleteTasks();

        $this->assertCount(count($completedTasksStates), $retrievedJobs);

        foreach ($retrievedJobs as $retrievedJob) {
            $this->assertTrue(in_array($retrievedJob->getState()->getName(), $completedTasksStates));
        }
    }
}