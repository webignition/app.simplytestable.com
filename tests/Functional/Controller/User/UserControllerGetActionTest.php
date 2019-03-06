<?php

namespace App\Tests\Functional\Controller\User;

use App\Entity\User;
use App\Services\AccountPlanService;
use App\Services\Team\InviteService;
use App\Services\UserAccountPlanService;
use App\Services\UserSummaryFactory;
use App\Tests\Factory\StripeApiFixtureFactory;
use App\Tests\Services\UserAccountPlanFactory;
use App\Tests\Services\UserFactory;

/**
 * @group Controller/UserController
 */
class UserControllerGetActionTest extends AbstractUserControllerTest
{
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

        $userFactory = self::$container->get(UserFactory::class);
        $this->users = $userFactory->createPublicPrivateAndTeamUserSet();

        $this->users['no-plan'] = $userFactory->create([
            UserFactory::KEY_EMAIL => 'no-plan@example.com',
            UserFactory::KEY_PLAN_NAME => null,
        ]);

        $this->users['invitee'] = $userFactory->create([
            UserFactory::KEY_EMAIL => 'invitee@example.com',
        ]);

        $this->users['premium'] = $userFactory->create([
            UserFactory::KEY_EMAIL => 'premium@example.com',
        ]);

        $this->users['basic-with-stripe-customer'] = $userFactory->create([
            UserFactory::KEY_EMAIL => 'basic-with-stripe-customer@example.com',
        ]);

        $userAccountPlanFactory = self::$container->get(UserAccountPlanFactory::class);

        $userAccountPlanFactory->create($this->users['premium'], 'agency', 'cus_aaaaaaaaaaaaa0');
        $userAccountPlanFactory->create($this->users['leader'], 'business', 'cus_aaaaaaaaaaaaa1');
        $userAccountPlanFactory->create($this->users['basic-with-stripe-customer'], 'personal', 'cus_aaaaaaaaaaaaa2');
        $userAccountPlanFactory->create($this->users['basic-with-stripe-customer'], 'basic', 'cus_aaaaaaaaaaaaa2');

        $teamInviteService = self::$container->get(InviteService::class);
        $teamInviteService->get($this->users['leader'], $this->users['invitee']);
        $teamInviteService->get($this->users['leader'], $this->users['premium']);
    }

    public function testGetActionGetRequest()
    {
        $router = self::$container->get('router');
        $requestUrl = $router->generate('user_get', [
            'email_canonical' => 'public@simplytestable.com',
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'GET',
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    /**
     * @dataProvider getActionDataProvider
     *
     * @param string[] $stripeApiHttpFixtures
     * @param string $userName
     * @param array $expectedResponseData
     * @param bool $expectedHasStripeCustomer
     */
    public function testGetAction(
        $stripeApiHttpFixtures,
        $userName,
        $expectedResponseData,
        $expectedHasStripeCustomer
    ) {
        StripeApiFixtureFactory::set($stripeApiHttpFixtures);

        $user = $this->users[$userName];
        $this->setUser($user);

        $response = $this->userController->getAction(
            self::$container->get(UserAccountPlanService::class),
            self::$container->get(AccountPlanService::class),
            self::$container->get(UserSummaryFactory::class),
            $user
        );

        $this->assertTrue($response->isSuccessful());

        $responseData = json_decode($response->getContent(), true);

        if ($expectedHasStripeCustomer) {
            $this->assertTrue(array_key_exists('stripe_customer', $responseData));
            $responseData['stripe_customer'] = [];
        } else {
            $this->assertFalse(array_key_exists('stripe_customer', $responseData));
        }

        $this->assertEquals($expectedResponseData, $responseData);
    }

    /**
     * @return array
     */
    public function getActionDataProvider()
    {
        return [
            'public' => [
                'stripeApiHttpFixtures' => [],
                'userName' => 'public',
                'expectedResponseData' => [
                    'email' => 'public@simplytestable.com',
                    'user_plan' => [
                        'plan' => [
                            'name' => 'public',
                            'is_premium' => false,
                        ],
                        'start_trial_period' => 30,
                    ],
                    'plan_constraints' => [
                        'urls_per_job' => 10,
                    ],
                    'team_summary' => [
                        'in' => false,
                        'has_invite' => false,
                    ],
                ],
                'expectedHasStripeCustomer' => false,
            ],
            'no-plan' => [
                'stripeApiHttpFixtures' => [],
                'userName' => 'no-plan',
                'expectedResponseData' => [
                    'email' => 'no-plan@example.com',
                    'user_plan' => [
                        'plan' => [
                            'name' => 'basic',
                            'is_premium' => false,
                        ],
                        'start_trial_period' => 30,
                    ],
                    'plan_constraints' => [
                        'urls_per_job' => 10,
                        'credits' => [
                            'limit' => 500,
                            'used' => 0,
                        ],
                    ],
                    'team_summary' => [
                        'in' => false,
                        'has_invite' => false,
                    ],
                ],
                'expectedHasStripeCustomer' => false,
            ],
            'private' => [
                'stripeApiHttpFixtures' => [],
                'userName' => 'private',
                'expectedResponseData' => [
                    'email' => 'private@example.com',
                    'user_plan' => [
                        'plan' => [
                            'name' => 'basic',
                            'is_premium' => false,
                        ],
                        'start_trial_period' => 30,
                    ],
                    'plan_constraints' => [
                        'urls_per_job' => 10,
                        'credits' => [
                            'limit' => 500,
                            'used' => 0,
                        ],
                    ],
                    'team_summary' => [
                        'in' => false,
                        'has_invite' => false,
                    ],
                ],
                'expectedHasStripeCustomer' => false,
            ],
            'premium' => [
                'stripeApiHttpFixtures' => [
                    StripeApiFixtureFactory::load('customer-nocard-hassub', [
                        '{stripe-customer-id}' => 'cus_aaaaaaaaaaaaa0',
                    ]),
                ],
                'userName' => 'premium',
                'expectedResponseData' => [
                    'email' => 'premium@example.com',
                    'user_plan' => [
                        'plan' => [
                            'name' => 'agency',
                            'is_premium' => true,
                        ],
                        'start_trial_period' => 30,
                        'stripe_customer' => 'cus_aaaaaaaaaaaaa0',
                    ],
                    'plan_constraints' => [
                        'urls_per_job' => 250,
                        'credits' => [
                            'limit' => 20000,
                            'used' => 0,
                        ],
                    ],
                    'team_summary' => [
                        'in' => false,
                        'has_invite' => false,
                    ],
                    'stripe_customer' => [],
                ],
                'expectedHasStripeCustomer' => true,
            ],
            'leader' => [
                'stripeApiHttpFixtures' => [
                    StripeApiFixtureFactory::load('customer-nocard-hassub', [
                        '{stripe-customer-id}' => 'cus_aaaaaaaaaaaaa1',
                    ]),
                ],
                'userName' => 'leader',
                'expectedResponseData' => [
                    'email' => 'leader@example.com',
                    'user_plan' => [
                        'plan' => [
                            'name' => 'business',
                            'is_premium' => true,
                        ],
                        'start_trial_period' => 30,
                        'stripe_customer' => 'cus_aaaaaaaaaaaaa1',
                    ],
                    'plan_constraints' => [
                        'urls_per_job' => 2500,
                        'credits' => [
                            'limit' => 100000,
                            'used' => 0,
                        ],
                    ],
                    'team_summary' => [
                        'in' => true,
                        'has_invite' => false,
                    ],
                    'stripe_customer' => [],
                ],
                'expectedHasStripeCustomer' => true,
            ],
            'member1' => [
                'stripeApiHttpFixtures' => [
                    StripeApiFixtureFactory::load('customer-nocard-hassub', [
                        '{stripe-customer-id}' => 'cus_aaaaaaaaaaaaa1',
                    ]),
                ],
                'userName' => 'member1',
                'expectedResponseData' => [
                    'email' => 'member1@example.com',
                    'user_plan' => [
                        'plan' => [
                            'name' => 'business',
                            'is_premium' => true,
                        ],
                        'start_trial_period' => 30,
                        'stripe_customer' => 'cus_aaaaaaaaaaaaa1',
                    ],
                    'plan_constraints' => [
                        'urls_per_job' => 2500,
                        'credits' => [
                            'limit' => 100000,
                            'used' => 0,
                        ],
                    ],
                    'team_summary' => [
                        'in' => true,
                        'has_invite' => false,
                    ],
                ],
                'expectedHasStripeCustomer' => false,
            ],
            'invitee' => [
                'stripeApiHttpFixtures' => [],
                'userName' => 'invitee',
                'expectedResponseData' => [
                    'email' => 'invitee@example.com',
                    'user_plan' => [
                        'plan' => [
                            'name' => 'basic',
                            'is_premium' => false,
                        ],
                        'start_trial_period' => 30,
                    ],
                    'plan_constraints' => [
                        'urls_per_job' => 10,
                        'credits' => [
                            'limit' => 500,
                            'used' => 0,
                        ],
                    ],
                    'team_summary' => [
                        'in' => false,
                        'has_invite' => true,
                    ],
                ],
                'expectedHasStripeCustomer' => false,
            ],
            'basic-with-stripe-customer' => [
                'stripeApiHttpFixtures' => [
                    StripeApiFixtureFactory::load('customer-nocard-hassub', [
                        '{stripe-customer-id}' => 'cus_aaaaaaaaaaaaa2',
                    ]),
                ],
                'userName' => 'basic-with-stripe-customer',
                'expectedResponseData' => [
                    'email' => 'basic-with-stripe-customer@example.com',
                    'user_plan' => [
                        'plan' => [
                            'name' => 'basic',
                            'is_premium' => false,
                        ],
                        'start_trial_period' => 30,
                        'stripe_customer' => 'cus_aaaaaaaaaaaaa2',
                    ],
                    'plan_constraints' => [
                        'urls_per_job' => 10,
                        'credits' => [
                            'limit' => 500,
                            'used' => 0,
                        ],
                    ],
                    'team_summary' => [
                        'in' => false,
                        'has_invite' => false,
                    ],
                    'stripe_customer' => [],
                ],
                'expectedHasStripeCustomer' => true,
            ],
        ];
    }
}
