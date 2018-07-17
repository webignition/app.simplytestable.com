<?php

namespace Tests\ApiBundle\Functional\Services;

use Doctrine\ORM\EntityManagerInterface;
use SimplyTestable\ApiBundle\Entity\Job\Ammendment;
use SimplyTestable\ApiBundle\Entity\Job\Configuration;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Job\RejectionReason;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use SimplyTestable\ApiBundle\Services\WebSiteService;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\TaskOutputFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

class JobServiceTest extends AbstractBaseTestCase
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
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobService = self::$container->get(JobService::class);
        $this->entityManager = self::$container->get('doctrine.orm.entity_manager');
        $this->jobFactory = new JobFactory(self::$container);
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
        $userFactory = new UserFactory(self::$container);
        $websiteService = self::$container->get(WebSiteService::class);
        $taskTypeService = self::$container->get(TaskTypeService::class);
        $stateService = self::$container->get(StateService::class);
        $jobTypeService = self::$container->get(JobTypeService::class);

        $user = $userFactory->create([
            UserFactory::KEY_EMAIL => $userEmail,
        ]);
        $website = $websiteService->get($url);

        $jobType = $jobTypeService->get($jobTypeName);

        $jobConfiguration = new Configuration();
        $jobConfiguration->setUser($user);
        $jobConfiguration->setWebsite($website);
        $jobConfiguration->setType($jobType);
        $jobConfiguration->setParameters(json_encode($jobParameters));

        foreach ($taskConfigurationCollectionValues as $taskConfigurationValues) {
            $taskType = $taskTypeService->get($taskConfigurationValues['type']);

            $taskConfiguration = new TaskConfiguration();
            $taskConfiguration->setType($taskType);
            $taskConfiguration->setIsEnabled($taskConfigurationValues['isEnabled']);
            $taskConfiguration->setOptions($taskConfigurationValues['options']);

            $jobConfiguration->addTaskConfiguration($taskConfiguration);
        }

        $job = $this->jobService->create($jobConfiguration);

        $this->assertInstanceOf(Job::class, $job);

        $jobStartingState = $stateService->get(Job::STATE_STARTING);

        $this->assertEquals($expectedUserEmail, $job->getUser()->getEmail());
        $this->assertEquals($expectedWebsiteUrl, $job->getWebsite()->getCanonicalUrl());
        $this->assertEquals($expectedJobTypeName, $job->getType()->getName());
        $this->assertEquals($expectedJobParameters, $job->getParameters()->getAsArray());
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
                'expectedJobParameters' => [],
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
                    [
                        'taskType' => 'CSS validation',
                        'options' => [
                            'css-foo' => 'css-bar',
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
        $stateService = self::$container->get(StateService::class);
        $state = $stateService->get($stateName);

        $job = $this->jobFactory->create();
        $job->setState($state);

        $this->entityManager->persist($job);
        $this->entityManager->flush();

        $this->assertEquals($expectedIsFinished, $this->jobService->isFinished($job));
        $this->assertEquals($expectedIsNew, Job::STATE_STARTING == $job->getState());
        $this->assertEquals($expectedIsPreparing, Job::STATE_PREPARING == $job->getState());
    }

    /**
     * @return array
     */
    public function isStateDataProvider()
    {
        return [
            Job::STATE_STARTING => [
                'stateName' => Job::STATE_STARTING,
                'expectedIsFinished' => false,
                'expectedIsNew' => true,
                'expectedIsPreparing' => false,
            ],
            Job::STATE_CANCELLED => [
                'stateName' => Job::STATE_CANCELLED,
                'expectedIsFinished' => true,
                'expectedIsNew' => false,
                'expectedIsPreparing' => false,
            ],
            Job::STATE_COMPLETED => [
                'stateName' => Job::STATE_COMPLETED,
                'expectedIsFinished' => true,
                'expectedIsNew' => false,
                'expectedIsPreparing' => false,
            ],
            Job::STATE_IN_PROGRESS => [
                'stateName' => Job::STATE_IN_PROGRESS,
                'expectedIsFinished' => false,
                'expectedIsNew' => false,
                'expectedIsPreparing' => false,
            ],
            Job::STATE_PREPARING => [
                'stateName' => Job::STATE_PREPARING,
                'expectedIsFinished' => false,
                'expectedIsNew' => false,
                'expectedIsPreparing' => true,
            ],
            Job::STATE_QUEUED => [
                'stateName' => Job::STATE_QUEUED,
                'expectedIsFinished' => false,
                'expectedIsNew' => false,
                'expectedIsPreparing' => false,
            ],
            Job::STATE_FAILED_NO_SITEMAP => [
                'stateName' => Job::STATE_FAILED_NO_SITEMAP,
                'expectedIsFinished' => true,
                'expectedIsNew' => false,
                'expectedIsPreparing' => false,
            ],
            Job::STATE_REJECTED => [
                'stateName' => Job::STATE_REJECTED,
                'expectedIsFinished' => true,
                'expectedIsNew' => false,
                'expectedIsPreparing' => false,
            ],
            Job::STATE_RESOLVING => [
                'stateName' => Job::STATE_RESOLVING,
                'expectedIsFinished' => false,
                'expectedIsNew' => false,
                'expectedIsPreparing' => false,
            ],
            Job::STATE_RESOLVED => [
                'stateName' => Job::STATE_RESOLVED,
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
        $stateService = self::$container->get(StateService::class);

        $job = $this->jobFactory->create();
        $job->setState($stateService->get($stateName));

        $this->entityManager->persist($job);
        $this->entityManager->flush();

        $this->jobService->cancel($job);

        $this->assertEquals($stateName, $job->getState());
    }

    /**
     * @return array
     */
    public function cancelFinishedJobDataProvider()
    {
        return [
            Job::STATE_REJECTED => [
                'stateName' => Job::STATE_REJECTED,
            ],
            Job::STATE_CANCELLED => [
                'stateName' => Job::STATE_CANCELLED,
            ],
            Job::STATE_COMPLETED => [
                'stateName' => Job::STATE_COMPLETED,
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
        $stateService = self::$container->get(StateService::class);
        $entityManager = self::$container->get('doctrine.orm.entity_manager');

        if ($resolveAndPrepare) {
            $job = $this->jobFactory->createResolveAndPrepare($jobValues);
        } else {
            $job = $this->jobFactory->create();
        }

        $tasks = $job->getTasks();

        if (count($tasks)) {
            /* @var Task $task */
            $task = $tasks->first();

            $task->setState($stateService->get(Task::STATE_IN_PROGRESS));

            $entityManager->persist($task);
            $entityManager->flush();
        }

        $this->jobService->cancel($job);

        $this->assertEquals(Job::STATE_CANCELLED, $job->getState());

        foreach ($tasks as $taskIndex => $task) {
            if ($taskIndex === 0) {
                $this->assertEquals(Task::STATE_AWAITING_CANCELLATION, $task->getState());
            } else {
                $this->assertEquals(Task::STATE_CANCELLED, $task->getState());
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
        $userFactory = new UserFactory(self::$container);
        $users = $userFactory->createPublicAndPrivateUserSet();

        $jobValues[JobFactory::KEY_USER] = $users[$user];

        $job = $this->jobFactory->create($jobValues);

        $userAccountPlanService = self::$container->get(UserAccountPlanService::class);
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
        $stateService = self::$container->get(StateService::class);
        $entityManager = self::$container->get('doctrine.orm.entity_manager');

        $finishedTaskStates = [
            Task::STATE_CANCELLED,
            Task::STATE_COMPLETED,
            Task::STATE_FAILED_NO_RETRY_AVAILABLE,
            Task::STATE_FAILED_RETRY_AVAILABLE,
            Task::STATE_FAILED_RETRY_LIMIT_REACHED,
            Task::STATE_SKIPPED,
        ];

        $incompleteTaskStates = [
            Task::STATE_QUEUED,
            Task::STATE_IN_PROGRESS,
            Task::STATE_AWAITING_CANCELLATION,
            Task::STATE_QUEUED_FOR_ASSIGNMENT,
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
            $state = $stateService->get($stateName);

            $task->setState($state);

            $entityManager->persist($task);
            $entityManager->flush();
        }

        foreach ($incompleteTaskStates as $stateIndex => $stateName) {
            $task = $tasksToChange[$stateIndex];
            $state = $stateService->get($stateName);

            $task->setState($state);

            $entityManager->persist($task);
            $entityManager->flush();
        }

        $this->jobService->cancelIncompleteTasks($job);

        foreach ($tasksToRemainUnchanged as $taskIndex => $task) {
            $expectedStateName = $finishedTaskStates[$taskIndex];

            $this->assertEquals($expectedStateName, $task->getState()->getName());
        }

        foreach ($tasksToChange as $taskIndex => $task) {
            $this->assertEquals(Task::STATE_CANCELLED, $task->getState()->getName());
        }
    }

    /**
     * @dataProvider completeFinishedJobDataProvider
     *
     * @param string $stateName
     */
    public function testCompleteFinishedJob($stateName)
    {
        $stateService = self::$container->get(StateService::class);

        $job = $this->jobFactory->create();

        $state = $stateService->get($stateName);
        $job->setState($state);

        $this->entityManager->persist($job);
        $this->entityManager->flush();

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
                'state' => Job::STATE_REJECTED,
            ],
            'finished; cancelled' => [
                'state' => Job::STATE_CANCELLED,
            ],
            'finished; completed' => [
                'state' => Job::STATE_COMPLETED,
            ],
            'finished; failed-no-sitemap' => [
                'state' => Job::STATE_FAILED_NO_SITEMAP,
            ],
        ];
    }

    public function testCompleteWithIncompleteTasks()
    {
        $job = $this->jobFactory->createResolveAndPrepare();
        $this->assertEquals(Job::STATE_QUEUED, $job->getState()->getName());

        $this->jobService->complete($job);

        $this->assertEquals(Job::STATE_QUEUED, $job->getState()->getName());
    }

    public function testComplete()
    {
        $stateService = self::$container->get(StateService::class);

        $job = $this->jobFactory->createResolveAndPrepare();
        $this->assertEquals(Job::STATE_QUEUED, $job->getState()->getName());
        $this->assertNull($job->getTimePeriod()->getEndDateTime());

        $this->jobFactory->setTaskStates($job, $stateService->get(Task::STATE_COMPLETED));

        $this->jobService->complete($job);

        $this->assertEquals(Job::STATE_COMPLETED, $job->getState()->getName());
        $this->assertInstanceOf(\DateTime::class, $job->getTimePeriod()->getEndDateTime());
    }

    public function testGetUnfinishedJobsWithTasksAndNoIncompleteTasks()
    {
        $stateNames = [
            Job::STATE_STARTING,
            Job::STATE_CANCELLED,
            Job::STATE_COMPLETED,
            Job::STATE_IN_PROGRESS,
            Job::STATE_PREPARING,
            Job::STATE_QUEUED,
            Job::STATE_FAILED_NO_SITEMAP,
            Job::STATE_REJECTED,
            Job::STATE_RESOLVING,
            Job::STATE_RESOLVED,
        ];

        $zeroTaskStates = [
            Job::STATE_STARTING,
            Job::STATE_RESOLVING,
            Job::STATE_RESOLVED,
        ];

        $completedTasksStates = [
            Job::STATE_PREPARING,
            Job::STATE_QUEUED,
        ];

        $userFactory = new UserFactory(self::$container);
        $user = $userFactory->create();

        $stateService = self::$container->get(StateService::class);

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

            $job->setState($stateService->get($stateName));

            $jobs[$stateName] = $job;
        }

        $taskCompletedState = $stateService->get(Task::STATE_COMPLETED);

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

    /**
     * @dataProvider rejectInWrongStateDataProvider
     *
     * @param string $stateName
     */
    public function testRejectInWrongState($stateName)
    {
        $stateService = self::$container->get(StateService::class);
        $jobRejectionReasonRepository = $this->entityManager->getRepository(RejectionReason::class);

        $job = $this->jobFactory->create();
        $job->setState($stateService->get($stateName));

        $this->entityManager->persist($job);
        $this->entityManager->flush();

        $this->jobService->reject($job, '');

        $this->assertEquals($stateName, $job->getState()->getName());

        $rejectionReason = $jobRejectionReasonRepository->findOneBy([
            'job' => $job,
        ]);

        $this->assertNull($rejectionReason);
    }

    /**
     * @return array
     */
    public function rejectInWrongStateDataProvider()
    {
        return [
            Job::STATE_CANCELLED => [
                'stateName' => Job::STATE_CANCELLED,
            ],
            Job::STATE_COMPLETED => [
                'stateName' => Job::STATE_COMPLETED,
            ],
            Job::STATE_IN_PROGRESS => [
                'stateName' => Job::STATE_IN_PROGRESS,
            ],
            Job::STATE_QUEUED => [
                'stateName' => Job::STATE_QUEUED,
            ],
            Job::STATE_FAILED_NO_SITEMAP => [
                'stateName' => Job::STATE_FAILED_NO_SITEMAP,
            ],
            Job::STATE_REJECTED => [
                'stateName' => Job::STATE_REJECTED,
            ],
            Job::STATE_RESOLVED => [
                'stateName' => Job::STATE_RESOLVED,
            ],
        ];
    }

    /**
     * @dataProvider rejectDataProvider
     *
     * @param string $userName
     * @param string $reason
     * @param string $constraintName
     */
    public function testReject($userName, $reason, $constraintName)
    {
        $userFactory = new UserFactory(self::$container);
        $user = $userFactory->createPublicAndPrivateUserSet()[$userName];

        $userAccountPlanService = self::$container->get(UserAccountPlanService::class);
        $jobRejectionReasonRepository = $this->entityManager->getRepository(RejectionReason::class);

        $job = $this->jobFactory->create([
            JobFactory::KEY_USER => $user,
        ]);

        if (empty($constraintName)) {
            $constraint = null;
        } else {
            $plan = $userAccountPlanService->getForUser($user)->getPlan();
            $constraint = $plan->getConstraintNamed($constraintName);
        }

        $this->jobService->reject($job, $reason, $constraint);

        $this->assertEquals(Job::STATE_REJECTED, $job->getState()->getName());

        $rejectionReason = $jobRejectionReasonRepository->findOneBy([
            'job' => $job,
        ]);

        $this->assertInstanceOf(RejectionReason::class, $rejectionReason);
        $this->assertEquals($reason, $rejectionReason->getReason());
        $this->assertEquals($constraint, $rejectionReason->getConstraint());
    }

    /**
     * @return array
     */
    public function rejectDataProvider()
    {
        return [
            'unroutable' => [
                'user' => 'public',
                'reason' => 'unroutable',
                'constraintName' => null,
            ],
            'plan-constraint-limit-reached' => [
                'user' => 'private',
                'reason' => 'plan-constraint-limit-reached',
                'constraintName' => 'credits_per_month',
            ],
        ];
    }

    /**
     * @dataProvider getCountOfTasksWithIssuesDataProvider
     *
     * @param array $jobValues
     * @param array $taskOutputValuesCollection
     * @param int $expectedCountOfTasksWithErrors
     * @param int $expectedCountOfTasksWithWarnings
     */
    public function testGetCountOfTasksWithIssues(
        $jobValues,
        $taskOutputValuesCollection,
        $expectedCountOfTasksWithErrors,
        $expectedCountOfTasksWithWarnings
    ) {
        $userFactory = new UserFactory(self::$container);
        $users = $userFactory->createPublicAndPrivateUserSet();

        if (isset($jobValues[JobFactory::KEY_USER])) {
            $jobValues[JobFactory::KEY_USER] = $users[$jobValues[JobFactory::KEY_USER]];
        }

        $job = $this->jobFactory->createResolveAndPrepare($jobValues);
        $tasks = $job->getTasks()->toArray();

        $taskOutputFactory = new TaskOutputFactory(self::$container);

        foreach ($tasks as $taskIndex => $task) {
            if (isset($taskOutputValuesCollection[$taskIndex])) {
                $taskOutputValues = $taskOutputValuesCollection[$taskIndex];

                $taskOutputFactory->create($task, $taskOutputValues);
            }
        }

        $this->assertEquals(
            $expectedCountOfTasksWithErrors,
            $this->jobService->getCountOfTasksWithErrors($job)
        );

        $this->assertEquals(
            $expectedCountOfTasksWithWarnings,
            $this->jobService->getCountOfTasksWithWarnings($job)
        );
    }

    /**
     * @return array
     */
    public function getCountOfTasksWithIssuesDataProvider()
    {
        return [
            'no output' => [
                'jobValues' => [],
                'taskOutputValuesCollection' => [],
                'expectedCountOfTasksWithErrors' => 0,
                'expectedCountOfTasksWithWarnings' => 0,
            ],
            'all tasks cancelled or awaiting cancellation' => [
                'jobValues' => [
                    JobFactory::KEY_TASKS => [
                        [
                            JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                        ],
                        [
                            JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                        ],
                        [
                            JobFactory::KEY_TASK_STATE => Task::STATE_AWAITING_CANCELLATION,
                        ],
                    ],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_ERROR_COUNT => 1,
                        TaskOutputFactory::KEY_WARNING_COUNT => 1,
                    ],
                    [
                        TaskOutputFactory::KEY_ERROR_COUNT => 1,
                        TaskOutputFactory::KEY_WARNING_COUNT => 1,
                    ],
                    [
                        TaskOutputFactory::KEY_ERROR_COUNT => 1,
                        TaskOutputFactory::KEY_WARNING_COUNT => 1,
                    ],
                ],
                'expectedCountOfTasksWithErrors' => 0,
                'expectedCountOfTasksWithWarnings' => 0,
            ],
            'tasks have errors' => [
                'jobValues' => [
                    JobFactory::KEY_TASKS => [
                        [
                            JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
                        ],
                        [
                            JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
                        ],
                        [
                            JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
                        ],
                    ],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_ERROR_COUNT => 3,
                        TaskOutputFactory::KEY_WARNING_COUNT => 5,
                    ],
                    [
                        TaskOutputFactory::KEY_ERROR_COUNT => 4,
                        TaskOutputFactory::KEY_WARNING_COUNT => 1,
                    ],
                    [
                        TaskOutputFactory::KEY_ERROR_COUNT => 0,
                        TaskOutputFactory::KEY_WARNING_COUNT => 3,
                    ],
                ],
                'expectedCountOfTasksWithErrors' => 2,
                'expectedCountOfTasksWithWarnings' => 3,
            ],
        ];
    }

    /**
     * @dataProvider getCancelledTaskCountDataProvider
     *
     * @param $jobValues
     * @param $expectedCount
     */
    public function testGetCancelledTaskCount($jobValues, $expectedCount)
    {
        $job = $this->jobFactory->createResolveAndPrepare($jobValues);

        $this->assertEquals(
            $expectedCount,
            $this->jobService->getCancelledTaskCount($job)
        );
    }

    /**
     * @return array
     */
    public function getCancelledTaskCountDataProvider()
    {
        return [
            'no cancelled tasks' => [
                'jobValues' => [],
                'expectedCount' => 0,
            ],
            'has cancelled tasks' => [
                'jobValues' => [
                    JobFactory::KEY_TASKS => [
                        [
                            JobFactory::KEY_TASK_STATE => Task::STATE_AWAITING_CANCELLATION,
                        ],
                        [
                            JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                        ],
                        [
                            JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
                        ],
                    ],
                ],
                'expectedCount' => 2,
            ],
        ];
    }

    /**
     * @dataProvider getSkippedTaskCountDataProvider
     *
     * @param $jobValues
     * @param $expectedCount
     */
    public function testGetSkippedTaskCount($jobValues, $expectedCount)
    {
        $job = $this->jobFactory->createResolveAndPrepare($jobValues);

        $this->assertEquals(
            $expectedCount,
            $this->jobService->getSkippedTaskCount($job)
        );
    }

    /**
     * @return array
     */
    public function getSkippedTaskCountDataProvider()
    {
        return [
            'no skipped tasks' => [
                'jobValues' => [],
                'expectedCount' => 0,
            ],
            'has skipped tasks' => [
                'jobValues' => [
                    JobFactory::KEY_TASKS => [
                        [
                            JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                        ],
                        [
                            JobFactory::KEY_TASK_STATE => Task::STATE_SKIPPED,
                        ],
                        [
                            JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
                        ],
                    ],
                ],
                'expectedCount' => 1,
            ],
        ];
    }

    public function testGetFinishedStateNames()
    {
        $this->assertEquals(
            [
                Job::STATE_REJECTED,
                Job::STATE_CANCELLED,
                Job::STATE_COMPLETED,
                Job::STATE_FAILED_NO_SITEMAP,
            ],
            $this->jobService->getFinishedStateNames()
        );
    }
}
