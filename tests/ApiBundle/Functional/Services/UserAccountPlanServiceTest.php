<?php

namespace Tests\ApiBundle\Functional\Services;

use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use Tests\ApiBundle\Factory\StripeApiFixtureFactory;
use Tests\ApiBundle\Factory\UserAccountPlanFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Exception\Services\UserAccountPlan\Exception as UserAccountPlanServiceException;

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

        $this->userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');
        $this->userFactory = new UserFactory($this->container);
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
        $accountPlanService = $this->container->get('simplytestable.services.accountplanservice');

        StripeApiFixtureFactory::set($httpFixtures);

        $user = $this->userFactory->create([
            UserFactory::KEY_PLAN_NAME => null,
        ]);

        $accountPlan = $accountPlanService->find($planName);

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
        $accountPlanService = $this->container->get('simplytestable.services.accountplanservice');

        $user = $this->userFactory->create([
            UserFactory::KEY_PLAN_NAME => $planName,
        ]);

        $currentUserAccountPlan = $this->userAccountPlanService->getForUser($user);

        $accountPlan = $accountPlanService->find($planName);

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
        $accountPlanService = $this->container->get('simplytestable.services.accountplanservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

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

        $accountPlan = $accountPlanService->find($planName);

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
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $userAccountPlanFactory = new UserAccountPlanFactory($this->container);
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

        $userAccountPlanRepository = $entityManager->getRepository(UserAccountPlan::class);
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

        $userAccountPlanFactory = new UserAccountPlanFactory($this->container);

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
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $userAccountPlanRepository = $entityManager->getRepository(UserAccountPlan::class);
        $userAccountPlanFactory = new UserAccountPlanFactory($this->container);

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

    public function testHasForUser()
    {
        $userAccountPlanFactory = new UserAccountPlanFactory($this->container);

        $user = $this->userFactory->create([
            UserFactory::KEY_PLAN_NAME => null,
        ]);

        $this->assertFalse($this->userAccountPlanService->hasForUser($user));

        $userAccountPlanFactory->create($user, 'personal');

        $this->assertTrue($this->userAccountPlanService->hasForUser($user));
    }

    public function testFindUsersWithNoPlan()
    {
        $this->userFactory->createPublicPrivateAndTeamUserSet();

        $usersCreatedWithNoPlan = [
            $this->userFactory->create([
                UserFactory::KEY_PLAN_NAME => null,
                UserFactory::KEY_EMAIL => 'user1@example.com',
            ]),
            $this->userFactory->create([
                UserFactory::KEY_PLAN_NAME => null,
                UserFactory::KEY_EMAIL => 'user2@example.com',
            ]),
        ];

        $usersWithNoPlan = $this->userAccountPlanService->findUsersWithNoPlan();

        $this->assertEquals($usersCreatedWithNoPlan, $usersWithNoPlan);
    }

    public function testFindAllByPlan()
    {
        $accountPlanService = $this->container->get('simplytestable.services.accountplanservice');

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

        $personalPlan = $accountPlanService->find('personal');

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

        $agencyPlan = $accountPlanService->find('agency');

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
