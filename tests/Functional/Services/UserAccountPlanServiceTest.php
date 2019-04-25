<?php

namespace App\Tests\Functional\Services;

use App\Entity\Account\Plan\Plan;
use App\Entity\UserAccountPlan;
use App\Services\AccountPlanService;
use App\Services\UserAccountPlanService;
use App\Tests\Factory\StripeApiFixtureFactory;
use App\Tests\Services\UserAccountPlanFactory;
use App\Tests\Services\UserFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Exception\Services\UserAccountPlan\Exception as UserAccountPlanServiceException;
use Doctrine\ORM\EntityManagerInterface;

class UserAccountPlanServiceTest extends AbstractBaseTestCase
{
    /**
     * @var UserAccountPlanService
     */
    private $userAccountPlanService;

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

        $this->userAccountPlanService = self::$container->get(UserAccountPlanService::class);
        $this->userFactory = self::$container->get(UserFactory::class);
    }

    public function testSubscribeUserBelongsToTeam()
    {
        $this->expectException(UserAccountPlanServiceException::class);
        $this->expectExceptionCode(UserAccountPlanServiceException::CODE_USER_IS_TEAM_MEMBER);

        $users = $this->userFactory->createPublicPrivateAndTeamUserSet();
        $user = $users['member1'];

        $accountPlan = new Plan();

        $this->userAccountPlanService->subscribe($user, $accountPlan);
    }

    /**
     * @dataProvider subscribeActionNoExistingUserAccountPlanDataProvider
     *
     * @param string[] $httpFixtures
     * @param string $planName
     * @param string $expectedStripeCustomer
     * @param int $expectedStartTrialPeriod
     */
    public function testSubscribeActionNoExistingUserAccountPlan(
        $httpFixtures,
        $planName,
        $expectedStripeCustomer,
        $expectedStartTrialPeriod
    ) {
        $accountPlanService = self::$container->get(AccountPlanService::class);
        $accountPlan = $accountPlanService->get($planName);

        StripeApiFixtureFactory::set($httpFixtures);

        $user = $this->userFactory->create([
            UserFactory::KEY_PLAN_NAME => null,
        ]);

        $userAccountPlan = $this->userAccountPlanService->subscribe($user, $accountPlan);

        $this->assertInstanceOf(UserAccountPlan::class, $userAccountPlan);

        $this->assertEquals($user, $userAccountPlan->getUser());
        $this->assertEquals($accountPlan, $userAccountPlan->getPlan());
        $this->assertTrue($userAccountPlan->getIsActive());

        $this->assertEquals($expectedStripeCustomer, $userAccountPlan->getStripeCustomer());
        $this->assertEquals($expectedStartTrialPeriod, $userAccountPlan->getStartTrialPeriod());
    }

    /**
     * @return array
     */
    public function subscribeActionNoExistingUserAccountPlanDataProvider()
    {
        return [
            'basic plan' => [
                'httpFixtures' => [],
                'planName' => 'basic',
                'expectedStripeCustomer' => null,
                'expectedStartTrialPeriod' => 30,
            ],
            'personal plan' => [
                'httpFixtures' => [
                    StripeApiFixtureFactory::load('customer-nocard-nosub', [
                        '{stripe-customer-id}' => 'foo',
                    ]),   // create customer
                    StripeApiFixtureFactory::load('customer-nocard-nosub'),   // retrieve customer
                    StripeApiFixtureFactory::load('customer-hascard-hassub'), // update subscription
                ],
                'planName' => 'personal',
                'expectedStripeCustomer' => 'foo',
                'expectedStartTrialPeriod' => 30,
            ],
        ];
    }

    /**
     * @dataProvider subscribeActionNewPlanIsCurrentPlanDataProvider
     *
     * @param string $planName
     */
    public function testSubscribeActionNewPlanIsCurrentPlan($planName)
    {
        $accountPlanService = self::$container->get(AccountPlanService::class);
        $accountPlan = $accountPlanService->get($planName);

        $user = $this->userFactory->create([
            UserFactory::KEY_PLAN_NAME => $planName,
        ]);

        $currentUserAccountPlan = $this->userAccountPlanService->getForUser($user);

        $userAccountPlan = $this->userAccountPlanService->subscribe($user, $accountPlan);

        $this->assertEquals($currentUserAccountPlan, $userAccountPlan);
    }

    public function subscribeActionNewPlanIsCurrentPlanDataProvider()
    {
        return [
            'basic plan' => [
                'planName' => 'basic',
            ],
            'personal plan' => [
                'planName' => 'personal',
            ],
        ];
    }

    /**
     * @dataProvider subscribeActionChangePlanDataProvider
     *
     * @param string[] $httpFixtures
     * @param string $currentPlanName
     * @param string $planName
     * @param int $expectedStartTrialPeriod
     */
    public function testSubscribeActionChangePlan($httpFixtures, $currentPlanName, $planName, $expectedStartTrialPeriod)
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $accountPlanService = self::$container->get(AccountPlanService::class);

        $nonPremiumAccountPlan = new Plan();
        $nonPremiumAccountPlan->setName('non-premium');
        $nonPremiumAccountPlan->setIsPremium(false);
        $nonPremiumAccountPlan->setIsVisible(true);
        $nonPremiumAccountPlan->setStripeId('non-premium');

        $entityManager->persist($nonPremiumAccountPlan);
        $entityManager->flush($nonPremiumAccountPlan);

        StripeApiFixtureFactory::set($httpFixtures);

        $user = $this->userFactory->create([
            UserFactory::KEY_PLAN_NAME => $currentPlanName,
        ]);

        $currentUserAccountPlan = $this->userAccountPlanService->getForUser($user);
        $currentUserAccountPlanStripeCustomer = $currentUserAccountPlan->getStripeCustomer();

        $accountPlan = $accountPlanService->get($planName);

        $userAccountPlan = $this->userAccountPlanService->subscribe($user, $accountPlan);

        $this->assertNotEquals($currentUserAccountPlan, $userAccountPlan);

        $this->assertFalse($currentUserAccountPlan->getIsActive());
        $this->assertTrue($userAccountPlan->getIsActive());

        $this->assertEquals($expectedStartTrialPeriod, $userAccountPlan->getStartTrialPeriod());

        if ($currentUserAccountPlan->getPlan()->getIsPremium() && !$userAccountPlan->getPlan()->getIsPremium()) {
            $this->assertEquals($currentUserAccountPlanStripeCustomer, $userAccountPlan->getStripeCustomer());
        }
    }

    public function subscribeActionChangePlanDataProvider()
    {
        return [
            'non-premium to non-premium' => [
                'httpFixtures' => [],
                'currentPlanName' => 'basic',
                'planName' => 'non-premium',
                'expectedStartTrialPeriod' => 30,
            ],
            'non-premium to premium' => [
                'httpFixtures' => [
                    StripeApiFixtureFactory::load('customer-nocard-nosub'),
                    StripeApiFixtureFactory::load('customer-nocard-nosub'),
                    StripeApiFixtureFactory::load('customer-hascard-hassub'),
                ],
                'currentPlanName' => 'basic',
                'planName' => 'personal',
                'expectedStartTrialPeriod' => 30,
            ],
            'premium to non-premium' => [
                'httpFixtures' => [
                    StripeApiFixtureFactory::load('customer-hascard-hassub', [], [
                        'subscription' => [
                            'trial_end' => time() + (86400 * 28),
                        ],
                    ]), // new plan get customer
                    StripeApiFixtureFactory::load('customer-nocard-nosub'),   // new plan update customer
                ],
                'currentPlanName' => 'personal',
                'planName' => 'basic',
                'expectedStartTrialPeriod' => 28,
            ],
            'premium to premium' => [
                'httpFixtures' => [
                    StripeApiFixtureFactory::load('customer-hascard-hassub', [], [
                        'subscription' => [
                            'trial_end' => time() + (86400 * 25),
                        ],
                    ]), // new plan get customer
                    StripeApiFixtureFactory::load('customer-hascard-hassub'), // new plan get customer
                    StripeApiFixtureFactory::load('customer-hascard-hassub'), // new plan update customer
                ],
                'currentPlanName' => 'personal',
                'planName' => 'agency',
                'expectedStartTrialPeriod' => 25,
            ],
        ];
    }

    /**
     * @dataProvider getForUserDataProvider
     *
     * @param array $userAccountPlansToCreate
     * @param string $userName
     * @param int $expectedUserAccountPlanIndex
     */
    public function testGetForUser($userAccountPlansToCreate, $userName, $expectedUserAccountPlanIndex)
    {
        $entityManager = self::$container->get(EntityManagerInterface::class);
        $userAccountPlanRepository = $entityManager->getRepository(UserAccountPlan::class);

        $userAccountPlanFactory = self::$container->get(UserAccountPlanFactory::class);
        $users = $this->userFactory->createPublicPrivateAndTeamUserSet();

        foreach ($userAccountPlansToCreate as $userAccountPlanToCreate) {
            $accountPlanUser = $users[$userAccountPlanToCreate['userName']];
            $currentUserAccountPlan = $userAccountPlanFactory->create(
                $accountPlanUser,
                $userAccountPlanToCreate['plan']
            );
            $currentUserAccountPlan->setIsActive($userAccountPlanToCreate['isActive']);

            $entityManager->persist($currentUserAccountPlan);
            $entityManager->flush($currentUserAccountPlan);
        }

        $user = $users[$userName];
        $userAccountPlan = $this->userAccountPlanService->getForUser($user);

        /* @var UserAccountPlan[] $userAccountPlans */
        $userAccountPlans = $userAccountPlanRepository->findAll();
        $expectedUserAccountPlan = $userAccountPlans[$expectedUserAccountPlanIndex];

        $this->assertEquals($expectedUserAccountPlan->getId(), $userAccountPlan->getId());
    }

    /**
     * @return array
     */
    public function getForUserDataProvider()
    {
        return [
            'public' => [
                'userAccountPlansToCreate' => [],
                'userName' => 'public',
                'expectedUserAccountPlanIndex' => 0,
            ],
            'private with no additional plans' => [
                'userAccountPlansToCreate' => [],
                'userName' => 'private',
                'expectedUserAccountPlanIndex' => 1,
            ],
            'private with many active plans' => [
                'userAccountPlansToCreate' => [
                    [
                        'userName' => 'private',
                        'plan' => 'agency',
                        'isActive' => true,
                    ],
                    [
                        'userName' => 'private',
                        'plan' => 'business',
                        'isActive' => true,
                    ],
                    [
                        'userName' => 'private',
                        'plan' => 'personal',
                        'isActive' => true,
                    ],
                ],
                'userName' => 'private',
                'expectedUserAccountPlanIndex' => 7,
            ],
            'private with many plans, not all active' => [
                'userAccountPlansToCreate' => [
                    [
                        'userName' => 'private',
                        'plan' => 'agency',
                        'isActive' => true,
                    ],
                    [
                        'userName' => 'private',
                        'plan' => 'business',
                        'isActive' => false,
                    ],
                    [
                        'userName' => 'private',
                        'plan' => 'personal',
                        'isActive' => null,
                    ],
                ],
                'userName' => 'private',
                'expectedUserAccountPlanIndex' => 5,
            ],
            'team leader gets own user account plan' => [
                'userAccountPlansToCreate' => [
                    [
                        'userName' => 'leader',
                        'plan' => 'personal',
                        'isActive' => true,
                    ],
                ],
                'userName' => 'leader',
                'expectedUserAccountPlanIndex' => 5,
            ],
            'team member gets leader user account plan' => [
                'userAccountPlansToCreate' => [
                    [
                        'userName' => 'leader',
                        'plan' => 'personal',
                        'isActive' => true,
                    ],
                ],
                'userName' => 'member1',
                'expectedUserAccountPlanIndex' => 5,
            ],
        ];
    }

    public function testRemoveCurrentForUser()
    {
        $user = $this->userFactory->create();

        $userAccountPlanFactory = self::$container->get(UserAccountPlanFactory::class);

        $agencyUserAccountPlan = $userAccountPlanFactory->create($user, 'agency');
        $businessUserAccountPlan = $userAccountPlanFactory->create($user, 'business');

        $this->assertEquals(
            $businessUserAccountPlan->getId(),
            $this->userAccountPlanService->getForUser($user)->getId()
        );

        $this->userAccountPlanService->removeCurrentForUser($user);

        $this->assertEquals(
            $agencyUserAccountPlan->getId(),
            $this->userAccountPlanService->getForUser($user)->getId()
        );
    }

    public function testDeactivateAllForUser()
    {
        $entityManager = self::$container->get(EntityManagerInterface::class);
        $userAccountPlanRepository = $entityManager->getRepository(UserAccountPlan::class);

        $userAccountPlanFactory = self::$container->get(UserAccountPlanFactory::class);

        $user = $this->userFactory->create([
            UserFactory::KEY_PLAN_NAME => null,
        ]);

        $findCriteria = [
            'user' => $user,
            'isActive' => true,
        ];

        $this->assertCount(
            0,
            $userAccountPlanRepository->findBy($findCriteria)
        );

        $userAccountPlanFactory->create($user, 'personal');
        $userAccountPlanFactory->create($user, 'agency');

        $this->assertCount(
            2,
            $userAccountPlanRepository->findBy($findCriteria)
        );

        $this->userAccountPlanService->deactivateAllForUser($user);

        $this->assertCount(
            0,
            $userAccountPlanRepository->findBy($findCriteria)
        );
    }

    public function testFindAllByPlan()
    {
        $accountPlanService = self::$container->get(AccountPlanService::class);

        $this->userFactory->create([
            UserFactory::KEY_PLAN_NAME => 'personal',
            UserFactory::KEY_EMAIL => 'user1@example.com',
        ]);

        $this->userFactory->create([
            UserFactory::KEY_PLAN_NAME => 'personal',
            UserFactory::KEY_EMAIL => 'user2@example.com',
        ]);

        $this->userFactory->create([
            UserFactory::KEY_PLAN_NAME => 'agency',
            UserFactory::KEY_EMAIL => 'user3@example.com',
        ]);

        $this->userFactory->create([
            UserFactory::KEY_PLAN_NAME => 'agency',
            UserFactory::KEY_EMAIL => 'user4@example.com',
        ]);

        $personalPlan = $accountPlanService->get('personal');

        $foundPersonalPlanUserAccountPlans = $this->userAccountPlanService->findAllByPlan($personalPlan);
        $foundPersonalPlanUserEmails = [];

        foreach ($foundPersonalPlanUserAccountPlans as $foundPersonalPlanUserAccountPlan) {
            $foundPersonalPlanUserEmails[] = $foundPersonalPlanUserAccountPlan->getUser()->getEmail();
        }

        $this->assertEquals(
            [
                'user1@example.com',
                'user2@example.com',
            ],
            $foundPersonalPlanUserEmails
        );

        $agencyPlan = $accountPlanService->get('agency');

        $foundAgencyPlanUserAccountPlans = $this->userAccountPlanService->findAllByPlan($agencyPlan);
        $foundAgencyPlanUserEmails = [];

        foreach ($foundAgencyPlanUserAccountPlans as $foundAgencyPlanUserAccountPlan) {
            $foundAgencyPlanUserEmails[] = $foundAgencyPlanUserAccountPlan->getUser()->getEmail();
        }

        $this->assertEquals(
            [
                'user3@example.com',
                'user4@example.com',
            ],
            $foundAgencyPlanUserEmails
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
