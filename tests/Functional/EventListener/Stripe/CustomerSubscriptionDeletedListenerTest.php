<?php

namespace App\Tests\Functional\EventListener\Stripe;

use GuzzleHttp\Psr7\Response;
use App\Event\Stripe\DispatchableEvent;
use App\Services\UserAccountPlanService;
use App\Tests\Services\StripeEventFactory;
use App\Tests\Services\UserFactory;

class CustomerSubscriptionDeletedListenerTest extends AbstractStripeEventListenerTest
{
    /**
     * @dataProvider onCustomerSubscriptionDeletedDataProvider
     *
     * @param array $stripeEventFixtures
     * @param string $userName
     * @param array $expectedWebClientRequestDataCollection
     * @param string $expectedPlanName
     */
    public function testOnCustomerSubscriptionDeleted(
        $stripeEventFixtures,
        $userName,
        $expectedWebClientRequestDataCollection,
        $expectedPlanName
    ) {
        $eventDispatcher = self::$container->get('event_dispatcher');
        $userAccountPlanService = self::$container->get(UserAccountPlanService::class);

        $this->httpClientService->appendFixtures([new Response()]);

        $userFactory = self::$container->get(UserFactory::class);
        $users = $userFactory->createPublicAndPrivateUserSet();
        $user = $users[$userName];

        $userAccountPlan = $userAccountPlanService->getForUser($user);
        $userAccountPlan->setStripeCustomer('non-empty value');

        $stripeEventFactory = self::$container->get(StripeEventFactory::class);
        $stripeEvent = $stripeEventFactory->createEvents($stripeEventFixtures, $user);

        $eventDispatcher->dispatch(
            'stripe_process.' . $stripeEvent->getType(),
            new DispatchableEvent($stripeEvent)
        );

        $this->assertTrue($stripeEvent->getIsProcessed());
        $this->assertWebClientRequests($expectedWebClientRequestDataCollection);

        $userAccountPlan = $userAccountPlanService->getForUser($user);

        $this->assertEquals(
            $expectedPlanName,
            $userAccountPlan->getPlan()->getName()
        );
    }

    /**
     * @return array
     */
    public function onCustomerSubscriptionDeletedDataProvider()
    {
        return [
            'customer.subscription.deleted; cancelled during trial' => [
                'stripeEventFixtures' => [
                    'customer.subscription.deleted' => [
                        'data' => [
                            'object' => [
                                'plan' => [
                                    'name' => 'Foo Plan Name',
                                    'amount' => 10000,
                                    'currency' => 'eur'
                                ],
                                'trial_start' => 1,
                                'trial_end' => 4,
                                'canceled_at' => 3,
                            ],
                        ],
                    ]
                ],
                'user' => 'public',
                'expectedWebClientRequestDataCollection' => [
                    [
                        'event' =>  'customer.subscription.deleted',
                        'user' => 'public@simplytestable.com',
                        'plan_name' => 'Foo Plan Name',
                        'actioned_by' => 'user',
                        'is_during_trial' => 1,
                        'trial_days_remaining' => 30,
                    ],
                ],
                'expectedPlanName' => 'public',
            ],
            'customer.subscription.deleted; cancelled during trial with non-relevant invoice payment failed' => [
                'stripeEventFixtures' => [
                    'invoice.payment_failed' => [
                        'data' => [
                            'object' => [
                                'lines' => [
                                    'data' => [
                                        [
                                            'id' => 'non-relevant subscription id',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'customer.subscription.deleted' => [
                        'data' => [
                            'object' => [
                                'plan' => [
                                    'name' => 'Foo Plan Name',
                                    'amount' => 10000,
                                    'currency' => 'eur'
                                ],
                                'trial_start' => 1,
                                'trial_end' => 4,
                                'canceled_at' => 3,
                            ],
                        ],
                    ],
                ],
                'user' => 'public',
                'expectedWebClientRequestDataCollection' => [
                    [
                        'event' =>  'customer.subscription.deleted',
                        'user' => 'public@simplytestable.com',
                        'plan_name' => 'Foo Plan Name',
                        'actioned_by' => 'user',
                        'is_during_trial' => 1,
                        'trial_days_remaining' => 30,
                    ],
                ],
                'expectedPlanName' => 'public',
            ],
            'customer.subscription.deleted; invoice payment failed' => [
                'stripeEventFixtures' => [
                    'invoice.payment_failed' => [],
                    'customer.subscription.deleted' => [
                        'data' => [
                            'object' => [
                                'plan' => [
                                    'name' => 'Foo Plan Name',
                                    'amount' => 10000,
                                    'currency' => 'eur'
                                ],
                                'trial_start' => 1,
                                'trial_end' => 4,
                                'canceled_at' => 5,
                            ],
                        ],
                    ],
                ],
                'user' => 'public',
                'expectedWebClientRequestDataCollection' => [
                    [
                        'event' =>  'customer.subscription.deleted',
                        'user' => 'public@simplytestable.com',
                        'plan_name' => 'Foo Plan Name',
                        'actioned_by' => 'system',
                    ],
                ],
                'expectedPlanName' => 'basic',
            ],
            'customer.subscription.deleted; user cancelled after trial' => [
                'stripeEventFixtures' => [
                    'customer.subscription.deleted' => [
                        'data' => [
                            'object' => [
                                'plan' => [
                                    'name' => 'Foo Plan Name',
                                    'amount' => 10000,
                                    'currency' => 'eur'
                                ],
                                'trial_start' => 1,
                                'trial_end' => 4,
                                'canceled_at' => 5,
                            ],
                        ],
                    ],
                ],
                'user' => 'public',
                'expectedWebClientRequestDataCollection' => [
                    [
                        'event' =>  'customer.subscription.deleted',
                        'user' => 'public@simplytestable.com',
                        'plan_name' => 'Foo Plan Name',
                        'actioned_by' => 'user',
                        'is_during_trial' => 0,
                    ],
                ],
                'expectedPlanName' => 'public',
            ],
            'customer.subscription.deleted; trial to active' => [
                'stripeEventFixtures' => [
                    'customer.subscription.updated.statuschange' => [
                        'data' => [
                            'object' => [
                                'status' => 'active',
                            ],
                            'previous_attributes' => [
                                'status' => 'trialing',
                            ],
                        ],
                    ],
                    'customer.subscription.deleted' => [
                        'data' => [
                            'object' => [
                                'plan' => [
                                    'name' => 'Foo Plan Name',
                                    'amount' => 10000,
                                    'currency' => 'eur'
                                ],
                                'trial_start' => 1,
                                'trial_end' => 4,
                                'canceled_at' => 5,
                            ],
                        ],
                    ],
                ],
                'user' => 'public',
                'expectedWebClientRequestDataCollection' => [],
                'expectedPlanName' => 'public',
            ],
        ];
    }
}
