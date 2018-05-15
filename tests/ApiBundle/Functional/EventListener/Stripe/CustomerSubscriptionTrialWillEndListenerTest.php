<?php

namespace Tests\ApiBundle\Functional\EventListener\Stripe;

use GuzzleHttp\Psr7\Response;
use SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use Tests\ApiBundle\Factory\StripeApiFixtureFactory;
use Tests\ApiBundle\Factory\StripeEventFactory;
use Tests\ApiBundle\Factory\UserFactory;

class CustomerSubscriptionTrialWillEndListenerTest extends AbstractStripeEventListenerTest
{
    /**
     * @dataProvider onCustomerSubscriptionTrialWillEndDataProvider
     *
     * @param array $stripeEventFixtures
     * @param string $userName
     * @param array $stripeApiHttpResponses
     * @param array $expectedWebClientRequestDataCollection
     */
    public function testOnCustomerSubscriptionTrialWillEnd(
        $stripeEventFixtures,
        $userName,
        $stripeApiHttpResponses,
        $expectedWebClientRequestDataCollection
    ) {
        $eventDispatcher = $this->container->get('event_dispatcher');
        $userAccountPlanService = $this->container->get(UserAccountPlanService::class);

        StripeApiFixtureFactory::set($stripeApiHttpResponses);

        $this->httpClientService->appendFixtures([new Response()]);

        $userFactory = new UserFactory($this->container);
        $users = $userFactory->createPublicAndPrivateUserSet();
        $user = $users[$userName];

        $userAccountPlan = $userAccountPlanService->getForUser($user);
        $userAccountPlan->setStripeCustomer('non-empty value');

        $stripeEventFactory = new StripeEventFactory($this->container);
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
    public function onCustomerSubscriptionTrialWillEndDataProvider()
    {
        return [
            'customer.subscription.trial_will_end; without discount' => [
                'stripeEventFixtures' => [
                    'customer.subscription.trial_will_end' => [
                        'data' => [
                            'object' => [
                                'plan' => [
                                    'name' => 'Foo Plan Name',
                                    'amount' => 10000,
                                    'currency' => 'eur'
                                ],
                                'trial_start' => 1,
                                'trial_end' => 4,
                            ],
                        ],
                    ]
                ],
                'user' => 'public',
                'stripeApiHttpFixtures' => [
                    StripeApiFixtureFactory::load('customer-nocard-hassub'),
                ],
                'expectedWebClientRequestDataCollection' => [
                    [
                        'event' =>  'customer.subscription.trial_will_end',
                        'user' => 'public@simplytestable.com',
                        'trial_end' => 4,
                        'has_card' => 0,
                        'plan_amount' => 10000,
                        'plan_name' => 'Foo Plan Name',
                        'plan_currency' => 'eur',
                    ],
                ],
            ],
            'customer.subscription.trial_will_end; with discount of 20% and has card' => [
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
                    'customer.subscription.trial_will_end' => [
                        'data' => [
                            'object' => [
                                'plan' => [
                                    'name' => 'Foo Plan Name',
                                    'amount' => 10000,
                                    'currency' => 'usd'
                                ],
                                'trial_start' => 1,
                                'trial_end' => 4,
                            ],
                        ],
                    ],
                ],
                'user' => 'private',
                'stripeApiHttpFixtures' => [
                    StripeApiFixtureFactory::load('customer-hascard-hassub'),
                ],
                'expectedWebClientRequestDataCollection' => [
                    [
                        'event' =>  'customer.subscription.trial_will_end',
                        'user' => 'private@example.com',
                        'trial_end' => 4,
                        'has_card' => 1,
                        'plan_amount' => 8000,
                        'plan_name' => 'Foo Plan Name',
                        'plan_currency' => 'usd',
                    ],
                ],
            ],
            'customer.subscription.trial_will_end; with discount of 30%' => [
                'stripeEventFixtures' => [
                    'customer.updated' => [
                        'data' => [
                            'object' => [
                                'discount' => [
                                    'coupon' => [
                                        'percent_off' => 30,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'customer.subscription.trial_will_end' => [
                        'data' => [
                            'object' => [
                                'plan' => [
                                    'name' => 'Foo Plan Name',
                                    'amount' => 333,
                                    'currency' => 'usd'
                                ],
                                'trial_start' => 1,
                                'trial_end' => 4,
                            ],
                        ],
                    ],
                ],
                'user' => 'private',
                'stripeApiHttpFixtures' => [
                    StripeApiFixtureFactory::load('customer-nocard-hassub'),
                ],
                'expectedWebClientRequestDataCollection' => [
                    [
                        'event' =>  'customer.subscription.trial_will_end',
                        'user' => 'private@example.com',
                        'trial_end' => 4,
                        'has_card' => 0,
                        'plan_amount' => 233,
                        'plan_name' => 'Foo Plan Name',
                        'plan_currency' => 'usd',
                    ],
                ],
            ],
        ];
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
