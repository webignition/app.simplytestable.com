<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services;

use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use SimplyTestable\ApiBundle\Tests\Factory\StripeApiFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Exception\Services\UserAccountPlan\Exception as UserAccountPlanServiceException;

class UserAccountPlanServiceTest extends BaseSimplyTestableTestCase
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
        $this->setExpectedException(
            UserAccountPlanServiceException::class,
            '',
            UserAccountPlanServiceException::CODE_USER_IS_TEAM_MEMBER
        );

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
     * @param string[] $httpFixtures
     * @param string $planName
     */
    public function testSubscribeActionNewPlanIsCurrentPlan($httpFixtures, $planName)
    {
        $accountPlanService = $this->container->get('simplytestable.services.accountplanservice');

        StripeApiFixtureFactory::set($httpFixtures);

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
                'httpFixtures' => [],
                'planName' => 'basic',
            ],
            'personal plan' => [
                'httpFixtures' => [
                    StripeApiFixtureFactory::load('customer-nocard-nosub'),
                    StripeApiFixtureFactory::load('customer-nocard-nosub'),
                    StripeApiFixtureFactory::load('customer-hascard-hassub'),
                ],
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

        $accountPlan = $accountPlanService->find($planName);

        $userAccountPlan = $this->userAccountPlanService->subscribe($user, $accountPlan);

        $this->assertNotEquals($currentUserAccountPlan, $userAccountPlan);

        $this->assertFalse($currentUserAccountPlan->getIsActive());
        $this->assertTrue($userAccountPlan->getIsActive());

        $this->assertEquals($expectedStartTrialPeriod, $userAccountPlan->getStartTrialPeriod());
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
                    StripeApiFixtureFactory::load('customer-nocard-nosub'),   // create user create customer
                    StripeApiFixtureFactory::load('customer-nocard-nosub'),   // create user get customer
                    StripeApiFixtureFactory::load('customer-hascard-hassub'), // create user update customer
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
                    StripeApiFixtureFactory::load('customer-nocard-nosub'),   // create user create customer
                    StripeApiFixtureFactory::load('customer-nocard-nosub'),   // create user get customer
                    StripeApiFixtureFactory::load('customer-hascard-hassub'), // create user update customer
                    StripeApiFixtureFactory::load('customer-hascard-hassub', [], [
                        'subscription' => [
                            'trial_end' => time() + (86400 * 30),
                        ],
                    ]), // new plan get customer
                    StripeApiFixtureFactory::load('customer-hascard-hassub'), // new plan get customer
                    StripeApiFixtureFactory::load('customer-hascard-hassub'), // new plan update customer
                ],
                'currentPlanName' => 'personal',
                'planName' => 'agency',
                'expectedStartTrialPeriod' => 30,
            ],
        ];
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
