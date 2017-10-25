<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\UserSummaryFactory;
use SimplyTestable\ApiBundle\Tests\Factory\StripeApiFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class UserSummaryFactoryTest extends BaseSimplyTestableTestCase
{
    /**
     * @var UserSummaryFactory
     */
    private $userSummaryFactory;

    /**
     * @var User[]
     */
    private $users;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userSummaryFactory = $this->container->get('simplytestable.services.usersummaryfactory');

        $userFactory = new UserFactory($this->container);
        $this->users = $userFactory->createPublicPrivateAndTeamUserSet();

        $this->users['basic-not-in-team'] = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'basic-not-in-team@example.com',
            UserFactory::KEY_PLAN_NAME => 'basic',
        ]);

        $this->users['personal-not-in-team'] = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'personal-not-in-team@example.com',
            UserFactory::KEY_PLAN_NAME => 'personal',
        ]);

        $this->users['has-team-invite'] = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'has-team-invite@example.com',
            UserFactory::KEY_PLAN_NAME => 'basic',
        ]);

        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');
        $teamInviteService->get($this->users['leader'], $this->users['has-team-invite']);
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param string[] $stripeApiHttpFixtures
     * @param string|null $stripeCustomerId
     * @param string $userName
     * @param bool $expectedHasStripeCustomer
     * @param array $expectedSerializedUserSummary
     */
    public function testCreate(
        $stripeApiHttpFixtures,
        $stripeCustomerId,
        $userName,
        $expectedHasStripeCustomer,
        $expectedSerializedUserSummary
    ) {
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        StripeApiFixtureFactory::set($stripeApiHttpFixtures);

        $user = $this->users[$userName];
        $this->setUser($user);

        if (!empty($stripeCustomerId)) {
            $userAccountPlan = $userAccountPlanService->getForUser($user);
            $userAccountPlan->setStripeCustomer($stripeCustomerId);

            $entityManager->persist($userAccountPlan);
            $entityManager->flush($userAccountPlan);
        }

        $userSummary = $this->userSummaryFactory->create();
        $serializedUserSummary = $userSummary->jsonSerialize();

        if ($expectedHasStripeCustomer) {
            $this->assertArrayHasKey('stripe_customer', $serializedUserSummary);

            $serializedUserSummary['stripe_customer'] = [
                'id' => $serializedUserSummary['stripe_customer']['id'],
            ];
        } else {
            $this->assertArrayNotHasKey('stripe_customer', $serializedUserSummary);
        }

        $this->assertEquals($expectedSerializedUserSummary, $serializedUserSummary);
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'basic user not in team' => [
                'stripeApiHttpFixtures' => [],
                'stripeCustomerId' => null,
                'userName' => 'basic-not-in-team',
                'expectedHasStripeCustomer' => false,
                'expectedSerializedUserSummary' => [
                    'email' => 'basic-not-in-team@example.com',
                    'user_plan' => [
                        'plan' => [
                            'name' => 'basic',
                            'is_premium' => false,
                        ],
                        'start_trial_period' => 30,
                    ],
                    'plan_constraints' => [
                        'credits' => [
                            'limit' => 500,
                            'used' => 0,
                        ],
                        'urls_per_job' => 10,
                    ],
                    'team_summary' => [
                        'in' => false,
                        'has_invite' => false,
                    ],
                ],
            ],
            'personal user not in team' => [
                'stripeApiHttpFixtures' => [
                    StripeApiFixtureFactory::load(
                        'customer-nocard-hassub',
                        [
                            '{stripe-customer-id}' => 'cus_aaaaaaaaaaaaa0',
                        ],
                        []
                    ),
                ],
                'stripeCustomerId' => 'cus_aaaaaaaaaaaaa0',
                'userName' => 'personal-not-in-team',
                'expectedHasStripeCustomer' => true,
                'expectedSerializedUserSummary' => [
                    'email' => 'personal-not-in-team@example.com',
                    'user_plan' => [
                        'plan' => [
                            'name' => 'personal',
                            'is_premium' => true,
                        ],
                        'start_trial_period' => 30,
                        'stripe_customer' => 'cus_aaaaaaaaaaaaa0',
                    ],
                    'plan_constraints' => [
                        'credits' => [
                            'limit' => 5000,
                            'used' => 0,
                        ],
                        'urls_per_job' => 50,
                    ],
                    'stripe_customer' => [
                        'id' => 'cus_aaaaaaaaaaaaa0',
                    ],
                    'team_summary' => [
                        'in' => false,
                        'has_invite' => false,
                    ],
                ],
            ],
            'team member' => [
                'stripeApiHttpFixtures' => [
                    StripeApiFixtureFactory::load(
                        'customer-nocard-hassub',
                        [
                            '{stripe-customer-id}' => 'cus_aaaaaaaaaaaaa1',
                        ],
                        []
                    ),
                ],
                'stripeCustomerId' => 'cus_aaaaaaaaaaaaa1',
                'userName' => 'member1',
                'expectedHasStripeCustomer' => false,
                'expectedSerializedUserSummary' => [
                    'email' => 'member1@example.com',
                    'user_plan' => [
                        'plan' => [
                            'name' => 'basic',
                            'is_premium' => false,
                        ],
                        'start_trial_period' => 30,
                        'stripe_customer' => 'cus_aaaaaaaaaaaaa1',
                    ],
                    'plan_constraints' => [
                        'credits' => [
                            'limit' => 500,
                            'used' => 0,
                        ],
                        'urls_per_job' => 10,
                    ],
                    'team_summary' => [
                        'in' => true,
                        'has_invite' => false,
                    ],
                ],
            ],
            'team leader' => [
                'stripeApiHttpFixtures' => [
                    StripeApiFixtureFactory::load(
                        'customer-nocard-hassub',
                        [
                            '{stripe-customer-id}' => 'cus_aaaaaaaaaaaaa1',
                        ],
                        []
                    ),
                ],
                'stripeCustomerId' => 'cus_aaaaaaaaaaaaa1',
                'userName' => 'leader',
                'expectedHasStripeCustomer' => true,
                'expectedSerializedUserSummary' => [
                    'email' => 'leader@example.com',
                    'user_plan' => [
                        'plan' => [
                            'name' => 'basic',
                            'is_premium' => false,
                        ],
                        'start_trial_period' => 30,
                        'stripe_customer' => 'cus_aaaaaaaaaaaaa1',
                    ],
                    'plan_constraints' => [
                        'credits' => [
                            'limit' => 500,
                            'used' => 0,
                        ],
                        'urls_per_job' => 10,
                    ],
                    'team_summary' => [
                        'in' => true,
                        'has_invite' => false,
                    ],
                    'stripe_customer' => [
                        'id' => 'cus_aaaaaaaaaaaaa1',
                    ],
                ],
            ],
            'not in team, has invite' => [
                'stripeApiHttpFixtures' => [],
                'stripeCustomerId' => null,
                'userName' => 'has-team-invite',
                'expectedHasStripeCustomer' => false,
                'expectedSerializedUserSummary' => [
                    'email' => 'has-team-invite@example.com',
                    'user_plan' => [
                        'plan' => [
                            'name' => 'basic',
                            'is_premium' => false,
                        ],
                        'start_trial_period' => 30,
                    ],
                    'plan_constraints' => [
                        'credits' => [
                            'limit' => 500,
                            'used' => 0,
                        ],
                        'urls_per_job' => 10,
                    ],
                    'team_summary' => [
                        'in' => false,
                        'has_invite' => true,
                    ],
                ],
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
