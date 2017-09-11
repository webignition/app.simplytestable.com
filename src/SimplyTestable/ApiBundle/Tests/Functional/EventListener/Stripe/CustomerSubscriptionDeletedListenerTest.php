<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\EventListener\Stripe;

use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class CustomerSubscriptionDeletedListenerTest extends AbstractStripeEventListenerTest
{
    /**
     * @dataProvider onCustomerSubscriptionDeletedDataProvider
     *
     * @param array $stripeEventFixtures
     * @param string $userName
     * @param array $stripeServiceResponses
     * @param array $expectedWebClientRequestData
     * @param string $expectedPlanName
     */
    public function testOnCustomerSubscriptionDeleted(
        $stripeEventFixtures,
        $userName,
        $stripeServiceResponses,
        $expectedWebClientRequestData,
        $expectedPlanName
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
                'stripeServiceResponses' => [],
                'expectedWebClientRequestData' => [
                    'event' =>  'customer.subscription.deleted',
                    'user' => 'public@simplytestable.com',
                    'plan_name' => 'Foo Plan Name',
                    'actioned_by' => 'user',
                    'is_during_trial' => 1,
                    'trial_days_remaining' => 30,
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
                'stripeServiceResponses' => [],
                'expectedWebClientRequestData' => [
                    'event' =>  'customer.subscription.deleted',
                    'user' => 'public@simplytestable.com',
                    'plan_name' => 'Foo Plan Name',
                    'actioned_by' => 'user',
                    'is_during_trial' => 1,
                    'trial_days_remaining' => 30,
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
                'stripeServiceResponses' => [],
                'expectedWebClientRequestData' => [
                    'event' =>  'customer.subscription.deleted',
                    'user' => 'public@simplytestable.com',
                    'plan_name' => 'Foo Plan Name',
                    'actioned_by' => 'system',
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
                'stripeServiceResponses' => [],
                'expectedWebClientRequestData' => [
                    'event' =>  'customer.subscription.deleted',
                    'user' => 'public@simplytestable.com',
                    'plan_name' => 'Foo Plan Name',
                    'actioned_by' => 'user',
                    'is_during_trial' => 0,
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
                'stripeServiceResponses' => [],
                'expectedWebClientRequestData' => null,
                'expectedPlanName' => 'public',
            ],
        ];
    }
}
