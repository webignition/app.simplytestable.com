<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\EventListener\Stripe;

use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class CustomerSubscriptionCreatedListenerTest extends AbstractStripeEventListenerTest
{
    /**
     * @dataProvider onCustomerSubscriptionCreatedDataProvider
     *
     * @param array $stripeEventFixtures
     * @param string $userName
     * @param array $stripeServiceResponses
     * @param array $expectedWebClientRequestData
     */
    public function testOnCustomerSubscriptionCreated(
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

        $this->assertEquals(
            $expectedWebClientRequestData,
            $lastHttpRequest->getPostFields()->getAll()
        );
    }

    /**
     * @return array
     */
    public function onCustomerSubscriptionCreatedDataProvider()
    {
        return [
            'customer.subscription.created.active' => [
                'stripeEventFixtures' => [
                    'customer.subscription.created.active' => [
                        'data' => [
                            'object' => [
                                'plan' => [
                                    'name' => 'Foo Plan Name',
                                    'amount' => 10000,
                                    'currency' => 'eur'
                                ],
                                'current_period_start' => 1,
                                'current_period_end' => 2,
                            ],
                        ],
                    ]
                ],
                'user' => 'public',
                'stripeServiceResponses' => [],
                'expectedWebClientRequestData' => [
                    'event' =>  'customer.subscription.created',
                    'user' => 'public@simplytestable.com',
                    'status' => 'active',
                    'plan_name' => 'Foo Plan Name',
                    'current_period_start' => 1,
                    'current_period_end' => 2,
                    'amount' => 10000,
                    'currency' => 'eur',
                ],
            ],
            'customer.subscription.created.trialing; active card' => [
                'stripeEventFixtures' => [
                    'customer.subscription.created.trialing' => [
                        'data' => [
                            'object' => [
                                'customer' => 'stripeCustomerId',
                                'plan' => [
                                    'name' => 'Bar Plan Name',
                                    'amount' => 5000,
                                    'currency' => 'gbp',
                                    'trial_period_days' => 10,
                                ],
                                'trial_start' => 3,
                                'trial_end' => 4,
                            ],
                        ],
                    ],
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
                    'event' =>  'customer.subscription.created',
                    'user' => 'private@example.com',
                    'status' => 'trialing',
                    'plan_name' => 'Bar Plan Name',
                    'has_card' => 1,
                    'trial_start' => 3,
                    'trial_end' => 4,
                    'trial_period_days' => 10,
                ],
            ],
            'customer.subscription.created.trialing; no card' => [
                'stripeEventFixtures' => [
                    'customer.subscription.created.trialing' => [
                        'data' => [
                            'object' => [
                                'plan' => [
                                    'name' => 'FooBar Plan Name',
                                    'amount' => 5000,
                                    'currency' => 'gbp',
                                    'trial_period_days' => 20,
                                ],
                                'trial_start' => 5,
                                'trial_end' => 6,
                            ],
                        ],
                    ]
                ],
                'user' => 'private',
                'stripeServiceResponses' => [
                    'getCustomer' => [],
                ],
                'expectedWebClientRequestData' => [
                    'event' =>  'customer.subscription.created',
                    'user' => 'private@example.com',
                    'status' => 'trialing',
                    'plan_name' => 'FooBar Plan Name',
                    'has_card' => 0,
                    'trial_start' => 5,
                    'trial_end' => 6,
                    'trial_period_days' => 20,
                ],
            ],
        ];
    }
}
