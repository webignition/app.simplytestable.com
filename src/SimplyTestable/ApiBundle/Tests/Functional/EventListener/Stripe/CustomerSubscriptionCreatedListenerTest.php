<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\EventListener\Stripe;

use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\StripeEventFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class CustomerSubscriptionCreatedListenerTest extends BaseSimplyTestableTestCase
{
    /**
     * @dataProvider onCustomerSubscriptionCreatedDataProvider
     *
     * @param string $stripeEventFixtureName
     * @param array $stripeEventFixtureModifiers
     * @param string $userName
     * @param array $stripeServiceResponses
     * @param array $expectedWebClientRequestData
     */
    public function testOnCustomerSubscriptionCreated(
        $stripeEventFixtureName,
        $stripeEventFixtureModifiers,
        $userName,
        $stripeServiceResponses,
        $expectedWebClientRequestData
    ) {
        $eventDispatcher = $this->container->get('event_dispatcher');
        $stripeEventService = $this->container->get('simplytestable.services.stripeeventservice');
        $httpClientService = $this->container->get('simplytestable.services.httpclientservice');
        $stripeService = $this->container->get('simplytestable.services.stripeservice');

        foreach ($stripeServiceResponses as $methodName => $responseData) {
            $stripeService->addResponseData($methodName, $responseData);
        }

        $this->queueHttpFixtures([
            HttpFixtureFactory::createSuccessResponse(),
        ]);

        $stripeEventFixture = StripeEventFixtureFactory::load($stripeEventFixtureName, $stripeEventFixtureModifiers);

        $userFactory = new UserFactory($this->container);
        $users = $userFactory->createPublicAndPrivateUserSet();
        $user = $users[$userName];

        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');

        $userAccountPlan = $userAccountPlanService->getForUser($user);
        $userAccountPlan->setStripeCustomer($stripeEventFixture['data']['object']['customer']);



        $stripeEvent = $stripeEventService->create(
            $stripeEventFixture['id'],
            $stripeEventFixture['type'],
            $stripeEventFixture['livemode'],
            json_encode($stripeEventFixture),
            $user
        );

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
                'stripeEventFixtureName' => 'customer.subscription.created.active',
                'stripeEventFixtureModifiers' => [
                    'data' => [
                        'object' => [
                            'customer' => 'stripeCustomerId',
                            'plan' => [
                                'name' => 'Foo Plan Name',
                                'amount' => 10000,
                                'currency' => 'eur'
                            ],
                            'current_period_start' => 1,
                            'current_period_end' => 2,
                        ],
                    ],
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
                'stripeEventFixtureName' => 'customer.subscription.created.trialing',
                'stripeEventFixtureModifiers' => [
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
                'stripeEventFixtureName' => 'customer.subscription.created.trialing',
                'stripeEventFixtureModifiers' => [
                    'data' => [
                        'object' => [
                            'customer' => 'stripeCustomerId',
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
