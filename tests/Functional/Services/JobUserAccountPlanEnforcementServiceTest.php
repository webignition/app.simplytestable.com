<?php

namespace App\Tests\Functional\Services;

use App\Entity\Task\Task;
use App\Entity\TimePeriod;
use App\Services\JobTypeService;
use App\Services\JobUserAccountPlanEnforcementService;
use App\Services\StateService;
use App\Services\TaskTypeService;
use App\Services\UserAccountPlanService;
use App\Services\UserService;
use App\Services\WebSiteService;
use App\Tests\Services\PlanFactory;
use App\Tests\Services\UserFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\ConstraintFactory;
use App\Tests\Services\JobFactory;

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

        $this->jobUserAccountPlanEnforcementService = self::$container->get(
            JobUserAccountPlanEnforcementService::class
        );
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
        $userFactory = self::$container->get(UserFactory::class);
        $users = $userFactory->createPublicAndPrivateUserSet();
        $user = $users[$userName];

        $websiteService = self::$container->get(WebSiteService::class);
        $entityManager = self::$container->get('doctrine.orm.entity_manager');

        $jobFactory = self::$container->get(JobFactory::class);

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
                        JobFactory::KEY_URL => 'http://example.com/',
                        JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                    ],
                    [
                        JobFactory::KEY_URL => 'http://example.com/',
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
                        JobFactory::KEY_URL => 'http://example.com/',
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
                        JobFactory::KEY_URL => 'http://example.com/',
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
                        JobFactory::KEY_URL => 'http://example.com/',
                        JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                    ],
                    [
                        JobFactory::KEY_URL => 'http://example.com/',
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
        $userFactory = self::$container->get(UserFactory::class);
        $users = $userFactory->createPublicAndPrivateUserSet();
        $user = $users[$userName];

        if ($removeLimit) {
            $userAccountPlanService = self::$container->get(UserAccountPlanService::class);
            $userAccountPlan = $userAccountPlanService->getForUser($user);
            $plan = $userAccountPlan->getPlan();

            $constraint = $plan->getConstraintNamed(JobUserAccountPlanEnforcementService::URLS_PER_JOB_CONSTRAINT_NAME);
            $plan->removeConstraint($constraint);

            $entityManager = self::$container->get('doctrine.orm.entity_manager');
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
        $jobFactory = self::$container->get(JobFactory::class);

        $userService = self::$container->get(UserService::class);
        $stateService = self::$container->get(StateService::class);
        $entityManager = self::$container->get('doctrine.orm.entity_manager');

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
                    Task::STATE_COMPLETED,
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
                    Task::STATE_COMPLETED,
                    Task::STATE_FAILED_NO_RETRY_AVAILABLE,
                    Task::STATE_FAILED_RETRY_AVAILABLE,
                    Task::STATE_FAILED_RETRY_LIMIT_REACHED,
                    Task::STATE_SKIPPED,
                ],
                'expectedCreditsUsed' => 5,
            ],
            'ten credits used' => [
                'jobValues' => [
                    JobFactory::KEY_TEST_TYPES => [
                        TaskTypeService::HTML_VALIDATION_TYPE,
                        TaskTypeService::CSS_VALIDATION_TYPE,
                        TaskTypeService::LINK_INTEGRITY_TYPE,
                    ],
                ],
                'taskStateNames' => [
                    Task::STATE_COMPLETED,
                    Task::STATE_FAILED_NO_RETRY_AVAILABLE,
                    Task::STATE_FAILED_RETRY_AVAILABLE,
                    Task::STATE_FAILED_RETRY_LIMIT_REACHED,
                    Task::STATE_SKIPPED,
                    Task::STATE_COMPLETED,
                    Task::STATE_FAILED_NO_RETRY_AVAILABLE,
                    Task::STATE_FAILED_RETRY_AVAILABLE,
                    Task::STATE_FAILED_RETRY_LIMIT_REACHED,
                ],
                'expectedCreditsUsed' => 9,
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
        $planFactory = self::$container->get(PlanFactory::class);
        $jobFactory = self::$container->get(JobFactory::class);

        $userService = self::$container->get(UserService::class);
        $userAccountPlanService = self::$container->get(UserAccountPlanService::class);
        $stateService = self::$container->get(StateService::class);
        $entityManager = self::$container->get('doctrine.orm.entity_manager');

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
                    Task::STATE_COMPLETED,
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
                    Task::STATE_COMPLETED,
                    Task::STATE_FAILED_NO_RETRY_AVAILABLE,
                    Task::STATE_FAILED_RETRY_AVAILABLE,
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

        $entityManger = self::$container->get('doctrine.orm.entity_manager');
        $entityManger->persist($timePeriod);
        $entityManger->flush();

        return $timePeriod;
    }
}
