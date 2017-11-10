<?php

namespace Tests\ApiBundle\Functional\Services;

use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\TimePeriod;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\JobUserAccountPlanEnforcementService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use SimplyTestable\ApiBundle\Services\UserService;
use SimplyTestable\ApiBundle\Services\WebSiteService;
use Tests\ApiBundle\Factory\ConstraintFactory;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\PlanFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

class JobUserAccountPlanEnforcementServiceTest extends AbstractBaseTestCase
{
    /**
     * @var JobUserAccountPlanEnforcementService
     */
    private $jobUserAccountPlanEnforcementService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobUserAccountPlanEnforcementService = $this->container->get(JobUserAccountPlanEnforcementService::class);
    }

    /**
     * @dataProvider isJobLimitReachedForWebsiteDataProvider
     *
     * @param string $userName
     * @param array $jobValuesCollection
     * @param string $websiteUrl
     * @param bool $expectedIsFullSiteLimitReached
     * @param bool $expectedIsSingleUrlLimitReached
     */
    public function testIsJobLimitReachedForWebsite(
        $userName,
        $jobValuesCollection,
        $websiteUrl,
        $expectedIsFullSiteLimitReached,
        $expectedIsSingleUrlLimitReached
    ) {
        $userFactory = new UserFactory($this->container);
        $users = $userFactory->createPublicAndPrivateUserSet();
        $user = $users[$userName];

        $websiteService = $this->container->get(WebSiteService::class);
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $jobFactory = new JobFactory($this->container);

        foreach ($jobValuesCollection as $jobValues) {
            $jobValues[JobFactory::KEY_USER] = $user;
            $job = $jobFactory->create($jobValues);

            $job->setTimePeriod($this->createTimePeriod());

            $entityManager->persist($job);
            $entityManager->flush();
        }

        $website = $websiteService->get($websiteUrl);

        $this->jobUserAccountPlanEnforcementService->setUser($user);

        $this->assertEquals(
            $expectedIsFullSiteLimitReached,
            $this->jobUserAccountPlanEnforcementService->isFullSiteJobLimitReachedForWebSite($website)
        );

        $this->assertEquals(
            $expectedIsSingleUrlLimitReached,
            $this->jobUserAccountPlanEnforcementService->isSingleUrlLimitReachedForWebsite($website)
        );
    }

    /**
     * @return array
     */
    public function isJobLimitReachedForWebsiteDataProvider()
    {
        return [
            'no limits not reached' => [
                'userName' => 'public',
                'jobValuesCollection' => [],
                'websiteUrl' => 'http://example.com/',
                'expectedIsFullSiteLimitReached' => false,
                'expectedIsSingleUrlLimitReached' => false,
            ],
            'no limits' => [
                'userName' => 'private',
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://example.com/',
                        JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://example.com/',
                        JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
                    ],
                ],
                'websiteUrl' => 'http://example.com/',
                'expectedIsFullSiteLimitReached' => false,
                'expectedIsSingleUrlLimitReached' => false,
            ],
            'full site limit reached' => [
                'userName' => 'public',
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://example.com/',
                        JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                    ],
                ],
                'websiteUrl' => 'http://example.com/',
                'expectedIsFullSiteLimitReached' => true,
                'expectedIsSingleUrlLimitReached' => false,
            ],
            'single url limit reached' => [
                'userName' => 'public',
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://example.com/',
                        JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
                    ],
                ],
                'websiteUrl' => 'http://example.com/',
                'expectedIsFullSiteLimitReached' => false,
                'expectedIsSingleUrlLimitReached' => true,
            ],
            'full site and single url limit reached' => [
                'userName' => 'public',
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://example.com/',
                        JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://example.com/',
                        JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
                    ],
                ],
                'websiteUrl' => 'http://example.com/',
                'expectedIsFullSiteLimitReached' => true,
                'expectedIsSingleUrlLimitReached' => true,
            ],
        ];
    }

    /**
     * @dataProvider isJobUrlLimitReachedDataProvider
     *
     * @param string $userName
     * @param int $urlCount
     * @param bool $removeLimit
     * @param bool $expectedIsJobUrlLimitReached
     */
    public function testIsJobUrlLimitReached(
        $userName,
        $urlCount,
        $removeLimit,
        $expectedIsJobUrlLimitReached
    ) {
        $userFactory = new UserFactory($this->container);
        $users = $userFactory->createPublicAndPrivateUserSet();
        $user = $users[$userName];

        if ($removeLimit) {
            $userAccountPlanService = $this->container->get(UserAccountPlanService::class);
            $userAccountPlan = $userAccountPlanService->getForUser($user);
            $plan = $userAccountPlan->getPlan();

            $constraint = $plan->getConstraintNamed(JobUserAccountPlanEnforcementService::URLS_PER_JOB_CONSTRAINT_NAME);
            $plan->removeConstraint($constraint);

            $entityManager = $this->container->get('doctrine.orm.entity_manager');
            $entityManager->persist($plan);
            $entityManager->flush();
        }

        $this->jobUserAccountPlanEnforcementService->setUser($user);

        $this->assertEquals(
            $expectedIsJobUrlLimitReached,
            $this->jobUserAccountPlanEnforcementService->isJobUrlLimitReached($urlCount)
        );
    }

    /**
     * @return array
     */
    public function isJobUrlLimitReachedDataProvider()
    {
        return [
            'urlCount zero' => [
                'userName' => 'public',
                'urlCount' => 0,
                'removeLimit' => false,
                'expectedIsJobUrlLimitReached' => false,
            ],
            'no limit' => [
                'userName' => 'public',
                'urlCount' => 11,
                'removeLimit' => true,
                'expectedIsJobUrlLimitReached' => false,
            ],
            'limit not reached' => [
                'userName' => 'public',
                'urlCount' => 1,
                'removeLimit' => false,
                'expectedIsJobUrlLimitReached' => false,
            ],
            'limit reached' => [
                'userName' => 'public',
                'urlCount' => 11,
                'removeLimit' => false,
                'expectedIsJobUrlLimitReached' => true,
            ],
        ];
    }

    /**
     * @dataProvider getCreditsUsedThisMonthDataProvider
     *
     * @param array $jobValues
     * @param string[] $taskStateNames
     * @param int $expectedCreditsUsed
     */
    public function testGetCreditsUsedThisMonth($jobValues, $taskStateNames, $expectedCreditsUsed)
    {
        $jobFactory = new JobFactory($this->container);

        $userService = $this->container->get(UserService::class);
        $stateService = $this->container->get(StateService::class);
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $user = $userService->getPublicUser();
        $job = $jobFactory->createResolveAndPrepare($jobValues);
        $tasks = $job->getTasks();

        foreach ($taskStateNames as $taskStateIndex => $taskStateName) {
            $taskState = $stateService->get($taskStateName);

            /* @var Task $task */
            $task = $tasks->get($taskStateIndex);
            $task->setState($taskState);
            $task->setTimePeriod($this->createTimePeriod());

            $entityManager->persist($task);
            $entityManager->flush($task);
        }

        $this->jobUserAccountPlanEnforcementService->setUser($user);
        $creditsUsed = $this->jobUserAccountPlanEnforcementService->getCreditsUsedThisMonth();

        $this->assertEquals($expectedCreditsUsed, $creditsUsed);
    }

    /**
     * @return array
     */
    public function getCreditsUsedThisMonthDataProvider()
    {
        return [
            'no credits used' => [
                'jobValues' => [],
                'taskStateNames' => [],
                'expectedCreditsUsed' => 0,
            ],
            'one credit used' => [
                'jobValues' => [],
                'taskStateNames' => [
                    TaskService::COMPLETED_STATE,
                ],
                'expectedCreditsUsed' => 1,
            ],
            'five credits used' => [
                'jobValues' => [
                    JobFactory::KEY_TEST_TYPES => [
                        TaskTypeService::HTML_VALIDATION_TYPE,
                        TaskTypeService::CSS_VALIDATION_TYPE,
                    ],
                ],
                'taskStateNames' => [
                    TaskService::COMPLETED_STATE,
                    TaskService::TASK_FAILED_NO_RETRY_AVAILABLE_STATE,
                    TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
                    TaskService::TASK_FAILED_RETRY_LIMIT_REACHED_STATE,
                    TaskService::TASK_SKIPPED_STATE,
                ],
                'expectedCreditsUsed' => 5,
            ],
            'ten credits used' => [
                'jobValues' => [
                    JobFactory::KEY_TEST_TYPES => [
                        TaskTypeService::HTML_VALIDATION_TYPE,
                        TaskTypeService::CSS_VALIDATION_TYPE,
                        TaskTypeService::JS_STATIC_ANALYSIS_TYPE,
                        TaskTypeService::LINK_INTEGRITY_TYPE,
                    ],
                ],
                'taskStateNames' => [
                    TaskService::COMPLETED_STATE,
                    TaskService::TASK_FAILED_NO_RETRY_AVAILABLE_STATE,
                    TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
                    TaskService::TASK_FAILED_RETRY_LIMIT_REACHED_STATE,
                    TaskService::TASK_SKIPPED_STATE,
                    TaskService::COMPLETED_STATE,
                    TaskService::TASK_FAILED_NO_RETRY_AVAILABLE_STATE,
                    TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
                    TaskService::TASK_FAILED_RETRY_LIMIT_REACHED_STATE,
                    TaskService::TASK_SKIPPED_STATE,
                ],
                'expectedCreditsUsed' => 10,
            ],
        ];
    }

    /**
     * @dataProvider isUserCreditLimitReachedDataProvider
     *
     * @param string[] $taskStateNames
     * @param array $planValues
     * @param bool $expectedIsLimitReached
     */
    public function testIsUserCreditLimitReached($taskStateNames, $planValues, $expectedIsLimitReached)
    {
        $planFactory = new PlanFactory($this->container);
        $jobFactory = new JobFactory($this->container);

        $userService = $this->container->get(UserService::class);
        $userAccountPlanService = $this->container->get(UserAccountPlanService::class);
        $stateService = $this->container->get(StateService::class);
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $user = $userService->getPublicUser();
        $plan = $planFactory->create($planValues);
        $userAccountPlanService->subscribe($user, $plan);

        $job = $jobFactory->createResolveAndPrepare();
        $tasks = $job->getTasks();

        foreach ($taskStateNames as $taskStateIndex => $taskStateName) {
            $taskState = $stateService->get($taskStateName);

            /* @var Task $task */
            $task = $tasks->get($taskStateIndex);
            $task->setState($taskState);
            $task->setTimePeriod($this->createTimePeriod());

            $entityManager->persist($task);
            $entityManager->flush($task);
        }

        $this->jobUserAccountPlanEnforcementService->setUser($user);

        $this->assertEquals(
            $expectedIsLimitReached,
            $this->jobUserAccountPlanEnforcementService->isUserCreditLimitReached()
        );
    }

    /**
     * @return array
     */
    public function isUserCreditLimitReachedDataProvider()
    {
        $creditsPerMonthConstraintName = JobUserAccountPlanEnforcementService::CREDITS_PER_MONTH_CONSTRAINT_NAME;
        $jobUrlLimitConstraintName = JobUserAccountPlanEnforcementService::URLS_PER_JOB_CONSTRAINT_NAME;

        return [
            'no limit' => [
                'taskStateNames' => [],
                'planValues' => [
                    PlanFactory::KEY_NAME => 'Plan',
                    PlanFactory::KEY_CONSTRAINTS => [
                        [
                            ConstraintFactory::KEY_NAME => $jobUrlLimitConstraintName,
                            ConstraintFactory::KEY_LIMIT => 1,
                        ],
                    ],
                ],
                'expectedIsLimitReached' => false,
            ],
            'limit not reached; no credits used' => [
                'taskStateNames' => [],
                'planValues' => [
                    PlanFactory::KEY_NAME => 'Plan',
                    PlanFactory::KEY_CONSTRAINTS => [
                        [
                            ConstraintFactory::KEY_NAME => $jobUrlLimitConstraintName,
                            ConstraintFactory::KEY_LIMIT => 1,
                        ],
                        [
                            ConstraintFactory::KEY_NAME => $creditsPerMonthConstraintName,
                            ConstraintFactory::KEY_LIMIT => 1,
                        ],
                    ],
                ],
                'expectedIsLimitReached' => false,
            ],
            'limit not reached; some credits used' => [
                'taskStateNames' => [
                    TaskService::COMPLETED_STATE,
                ],
                'planValues' => [
                    PlanFactory::KEY_NAME => 'Plan',
                    PlanFactory::KEY_CONSTRAINTS => [
                        [
                            ConstraintFactory::KEY_NAME => $jobUrlLimitConstraintName,
                            ConstraintFactory::KEY_LIMIT => 20,
                        ],
                        [
                            ConstraintFactory::KEY_NAME => $creditsPerMonthConstraintName,
                            ConstraintFactory::KEY_LIMIT => 10,
                        ],
                    ],
                ],
                'expectedIsLimitReached' => false,
            ],
            'limit reached' => [
                'taskStateNames' => [
                    TaskService::COMPLETED_STATE,
                    TaskService::TASK_FAILED_NO_RETRY_AVAILABLE_STATE,
                    TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
                ],
                'planValues' => [
                    PlanFactory::KEY_NAME => 'Plan',
                    PlanFactory::KEY_CONSTRAINTS => [
                        [
                            ConstraintFactory::KEY_NAME => $jobUrlLimitConstraintName,
                            ConstraintFactory::KEY_LIMIT => 20,
                        ],
                        [
                            ConstraintFactory::KEY_NAME => $creditsPerMonthConstraintName,
                            ConstraintFactory::KEY_LIMIT => 2,
                        ],
                    ],
                ],
                'expectedIsLimitReached' => true,
            ],
        ];
    }

    /**
     * @return TimePeriod
     */
    private function createTimePeriod()
    {
        $startDateTime = new \DateTime('first day of this month');
        $endDateTime = new \DateTime('last day of this month');
        $timePeriod = new TimePeriod();
        $timePeriod->setStartDateTime($startDateTime);
        $timePeriod->setEndDateTime($endDateTime);

        $entityManger = $this->container->get('doctrine.orm.entity_manager');
        $entityManger->persist($timePeriod);
        $entityManger->flush();

        return $timePeriod;
    }
}
