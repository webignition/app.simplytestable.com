<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\EventListener\Stripe;

use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class CustomerSubscriptionUpdatedTest extends AbstractStripeEventListenerTest
{
    /**
     * @dataProvider onCustomerSubscriptionUpdatedDataProvider
     *
     * @param array $stripeEventFixtures
     * @param string $userName
     * @param array $stripeServiceResponses
     * @param array $expectedWebClientRequestData
     */
    public function testOnCustomerSubscriptionUpdated(
        $stripeEventFixtures,
        $userName,
        $stripeServiceResponses,
        $expectedWebClientRequestData
    ) {
        $eventDispatcher = $this->container->get('event_dispatcher');
        $httpClientService = $this->container->get('simplytestable.services.httpclientservice');
        $stripeService = $this->container->get('simplytestable.services.stripeservice');
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');

        foreach ($stripeServiceResponses as $methodName => $responseData) {
            $stripeService->addResponseData($methodName, $responseData);
        }

        $this->queueHttpFixtures([
            HttpFixtureFactory::createSuccessResponse(),
        ]);

        $userFactory = new UserFactory($this->container);
        $users = $userFactory->createPublicAndPrivateUserSet();
        $user = $users[$userName];

        $userAccountPlan = $userAccountPlanService->getForUser($user);
        $userAccountPlan->setStripeCustomer('non-empty value');

        $stripeEvent = $this->createStripeEvents($stripeEventFixtures, $user);

        $eventDispatcher->dispatch(
            'stripe_process.' . $stripeEvent->getType(),
            new DispatchableEvent($stripeEvent)
        );

        $this->assertTrue($stripeEvent->getIsProcessed());

        /* @var EntityEnclosingRequestInterface $lastHttpRequest */
        $lastHttpRequest = $httpClientService->getHistoryPlugin()->getLastRequest();

        if (null === $expectedWebClientRequestData) {
            $this->assertNull($lastHttpRequest);
        } else {
            $this->assertEquals(
                $expectedWebClientRequestData,
                $lastHttpRequest->getPostFields()->getAll()
            );
        }
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
                'stripeServiceResponses' => [],
                'expectedWebClientRequestData' => [
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
                'stripeServiceResponses' => [],
                'expectedWebClientRequestData' => [
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
                'stripeServiceResponses' => [],
                'expectedWebClientRequestData' => [
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
                'stripeServiceResponses' => [],
                'expectedWebClientRequestData' => [
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
                'stripeServiceResponses' => [],
                'expectedWebClientRequestData' => [
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
                'stripeServiceResponses' => [
                    'getCustomer' => [
                        'active_card' => [
                            'exp_month' => '01',
                            'exp_year' => '99',
                            'last4' => '1234',
                            'type' => 'Foo'
                        ]
                    ],
                ],
                'expectedWebClientRequestData' => [
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
                'stripeServiceResponses' => [],
                'expectedWebClientRequestData' => null,
            ],
        ];
    }
}
