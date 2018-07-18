<?php

namespace Tests\ApiBundle\Functional\EventListener\Stripe;

use GuzzleHttp\Psr7\Response;
use SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use Tests\ApiBundle\Factory\StripeApiFixtureFactory;
use Tests\ApiBundle\Factory\StripeEventFactory;
use Tests\ApiBundle\Factory\UserFactory;

class CustomerSubscriptionUpdatedTest extends AbstractStripeEventListenerTest
{
    /**
     * @dataProvider onCustomerSubscriptionUpdatedDataProvider
     *
     * @param array $stripeEventFixtures
     * @param string $userName
     * @param array $stripeApiHttpFixtures
     * @param array $expectedWebClientRequestDataCollection
     */
    public function testOnCustomerSubscriptionUpdated(
        $stripeEventFixtures,
        $userName,
        $stripeApiHttpFixtures,
        $expectedWebClientRequestDataCollection
    ) {
        $eventDispatcher = self::$container->get('event_dispatcher');
        $userAccountPlanService = self::$container->get(UserAccountPlanService::class);

        StripeApiFixtureFactory::set($stripeApiHttpFixtures);

        $this->httpClientService->appendFixtures([new Response()]);

        $userFactory = new UserFactory(self::$container);
        $users = $userFactory->createPublicAndPrivateUserSet();
        $user = $users[$userName];

        $userAccountPlan = $userAccountPlanService->getForUser($user);
        $userAccountPlan->setStripeCustomer('non-empty value');

        $stripeEventFactory = new StripeEventFactory(self::$container);
        $stripeEvent = $stripeEventFactory->createEvents($stripeEventFixtures, $user);

        $eventDispatcher->dispatch(
            'stripe_process.' . $stripeEvent->getType(),
            new DispatchableEvent($stripeEvent)
        );

        $this->assertTrue($stripeEvent->getIsProcessed());
        $this->assertWebClientRequests($expectedWebClientRequestDataCollection);
    }

    /**
     * @return array
     */
    public function onCustomerSubscriptionUpdatedDataProvider()
    {
        return [
            'customer.subscription.updated.planchange; without discount; status active' => [
                'stripeEventFixtures' => [
                    'customer.subscription.updated.planchange' => [
                        'data' => [
                            'object' => [
                                'plan' => [
                                    'name' => 'New Plan Name',
                                    'amount' => 10000,
                                    'currency' => 'eur'
                                ],
                                'status' => 'active',
                            ],
                            'previous_attributes' => [
                                'plan' => [
                                    'name' => 'Old Plan Name',
                                    'amount' => 5000,
                                    'currency' => 'eur'
                                ],
                            ],
                        ],
                    ]
                ],
                'user' => 'public',
                'stripeApiHttpFixtures' => [],
                'expectedWebClientRequestDataCollection' => [
                    [
                        'event' =>  'customer.subscription.updated',
                        'user' => 'public@simplytestable.com',
                        'is_plan_change' => 1,
                        'currency' => 'eur',
                        'old_plan' => 'Old Plan Name',
                        'new_plan' => 'New Plan Name',
                        'new_amount' => 10000,
                        'subscription_status' => 'active',
                    ],
                ],
            ],
            'customer.subscription.updated.planchange; without discount; status trialing' => [
                'stripeEventFixtures' => [
                    'customer.subscription.updated.planchange' => [
                        'data' => [
                            'object' => [
                                'plan' => [
                                    'name' => 'New Plan Name',
                                    'amount' => 10000,
                                    'currency' => 'eur'
                                ],
                                'status' => 'trialing',
                                'trial_end' => 4,
                            ],
                            'previous_attributes' => [
                                'plan' => [
                                    'name' => 'Old Plan Name',
                                    'amount' => 5000,
                                    'currency' => 'eur'
                                ],
                            ],
                        ],
                    ]
                ],
                'user' => 'private',
                'stripeApiHttpFixtures' => [],
                'expectedWebClientRequestDataCollection' => [
                    [
                        'event' =>  'customer.subscription.updated',
                        'user' => 'private@example.com',
                        'is_plan_change' => 1,
                        'currency' => 'eur',
                        'old_plan' => 'Old Plan Name',
                        'new_plan' => 'New Plan Name',
                        'new_amount' => 10000,
                        'subscription_status' => 'trialing',
                        'trial_end' => 4,
                    ],
                ],
            ],
            'customer.subscription.updated.planchange; with discount; status active' => [
                'stripeEventFixtures' => [
                    'customer.updated' => [
                        'data' => [
                            'object' => [
                                'discount' => [
                                    'coupon' => [
                                        'percent_off' => 20,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'customer.subscription.updated.planchange' => [
                        'data' => [
                            'object' => [
                                'plan' => [
                                    'name' => 'New Plan Name',
                                    'amount' => 10000,
                                    'currency' => 'eur'
                                ],
                                'status' => 'active',
                            ],
                            'previous_attributes' => [
                                'plan' => [
                                    'name' => 'Old Plan Name',
                                    'amount' => 5000,
                                    'currency' => 'eur'
                                ],
                            ],
                        ],
                    ]
                ],
                'user' => 'public',
                'stripeApiHttpFixtures' => [],
                'expectedWebClientRequestDataCollection' => [
                    [
                        'event' =>  'customer.subscription.updated',
                        'user' => 'public@simplytestable.com',
                        'is_plan_change' => 1,
                        'currency' => 'eur',
                        'old_plan' => 'Old Plan Name',
                        'new_plan' => 'New Plan Name',
                        'new_amount' => 8000,
                        'subscription_status' => 'active',
                    ],
                ],
            ],
            'customer.subscription.updated.planchange; with discount; status trialing' => [
                'stripeEventFixtures' => [
                    'customer.updated' => [
                        'data' => [
                            'object' => [
                                'discount' => [
                                    'coupon' => [
                                        'percent_off' => 20,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'customer.subscription.updated.planchange' => [
                        'data' => [
                            'object' => [
                                'plan' => [
                                    'name' => 'New Plan Name',
                                    'amount' => 10000,
                                    'currency' => 'eur'
                                ],
                                'status' => 'trialing',
                                'trial_end' => 4,
                            ],
                            'previous_attributes' => [
                                'plan' => [
                                    'name' => 'Old Plan Name',
                                    'amount' => 5000,
                                    'currency' => 'eur'
                                ],
                            ],
                        ],
                    ]
                ],
                'user' => 'private',
                'stripeApiHttpFixtures' => [],
                'expectedWebClientRequestDataCollection' => [
                    [
                        'event' =>  'customer.subscription.updated',
                        'user' => 'private@example.com',
                        'is_plan_change' => 1,
                        'currency' => 'eur',
                        'old_plan' => 'Old Plan Name',
                        'new_plan' => 'New Plan Name',
                        'new_amount' => 8000,
                        'subscription_status' => 'trialing',
                        'trial_end' => 4,
                    ],
                ],
            ],
            'customer.subscription.updated.statuschange; trialing to active, no card' => [
                'stripeEventFixtures' => [
                    'customer.subscription.updated.statuschange' => [
                        'data' => [
                            'object' => [
                                'status' => 'active',
                                'plan' => [
                                    'name' => 'Plan Name',
                                    'amount' => 10000,
                                    'currency' => 'eur'
                                ],
                            ],
                            'previous_attributes' => [
                                'status' => 'trialing',
                            ],
                        ],
                    ]
                ],
                'user' => 'private',
                'stripeApiHttpFixtures' => [
                    StripeApiFixtureFactory::load('customer-nocard-hassub'),
                ],
                'expectedWebClientRequestDataCollection' => [
                    [
                        'event' =>  'customer.subscription.updated',
                        'user' => 'private@example.com',
                        'is_status_change' => 1,
                        'previous_subscription_status' => 'trialing',
                        'subscription_status' => 'active',
                        'plan_name' => 'Plan Name',
                        'plan_amount' => 10000,
                        'has_card' => 0,
                        'currency' => 'eur',
                    ],
                ],
            ],
            'customer.subscription.updated.statuschange; trialing to active, has card' => [
                'stripeEventFixtures' => [
                    'customer.subscription.updated.statuschange' => [
                        'data' => [
                            'object' => [
                                'status' => 'active',
                                'plan' => [
                                    'name' => 'Plan Name',
                                    'amount' => 10000,
                                    'currency' => 'eur'
                                ],
                            ],
                            'previous_attributes' => [
                                'status' => 'trialing',
                            ],
                        ],
                    ]
                ],
                'user' => 'private',
                'stripeApiHttpFixtures' => [
                    StripeApiFixtureFactory::load('customer-hascard-hassub'),
                ],
                'expectedWebClientRequestDataCollection' => [
                    [
                        'event' =>  'customer.subscription.updated',
                        'user' => 'private@example.com',
                        'is_status_change' => 1,
                        'previous_subscription_status' => 'trialing',
                        'subscription_status' => 'active',
                        'plan_name' => 'Plan Name',
                        'plan_amount' => 10000,
                        'has_card' => 1,
                        'currency' => 'eur',
                    ],
                ],
            ],
            'customer.subscription.updated.statuschange; not trialing to active' => [
                'stripeEventFixtures' => [
                    'customer.subscription.updated.statuschange' => [
                        'data' => [
                            'object' => [
                                'status' => 'foo',
                            ],
                            'previous_attributes' => [
                                'status' => 'bar',
                            ],
                        ],
                    ]
                ],
                'user' => 'private',
                'stripeApiHttpFixtures' => [],
                'expectedWebClientRequestDataCollection' => [],
            ],
        ];
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
