<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\EventListener\Stripe;

use Psr\Log\LoggerInterface;
use SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent;
use SimplyTestable\ApiBundle\EventListener\Stripe\CustomerSubscriptionCreatedListener;
use SimplyTestable\ApiBundle\Services\AccountPlanService;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use SimplyTestable\ApiBundle\Services\StripeEventService;
use SimplyTestable\ApiBundle\Services\StripeService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use SimplyTestable\ApiBundle\Tests\Factory\CurlExceptionFactory;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\StripeEventFactory;
use SimplyTestable\ApiBundle\Tests\Factory\StripeEventFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use SimplyTestable\ApiBundle\Entity\Stripe\Event as StripeEvent;

class CustomerSubscriptionCreatedListenerTest extends AbstractStripeEventListenerTest
{
    public function testOnCustomerSubscriptionCreatedWebClientRequestFailure()
    {
        $eventDispatcher = $this->container->get('event_dispatcher');
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');
        $userService = $this->container->get('simplytestable.services.userservice');

        $this->queueHttpFixtures([
            CurlExceptionFactory::create('Operation timed out', 28),
        ]);

        $user = $userService->getPublicUser();

        $userAccountPlan = $userAccountPlanService->getForUser($user);
        $userAccountPlan->setStripeCustomer('non-empty value');

        $stripeEventFactory = new StripeEventFactory($this->container);

        $stripeEvent = $stripeEventFactory->createEvents([
            'customer.subscription.created.active' => [
                'data' => [],
            ]
        ], $user);

        $eventDispatcher->dispatch(
            'stripe_process.' . $stripeEvent->getType(),
            new DispatchableEvent($stripeEvent)
        );

        $this->assertTrue($stripeEvent->getIsProcessed());
    }

    /**
     * @dataProvider webClientPropertiesDataProvider
     *
     * @param array $webClientProperties
     */
    public function testOnCustomerSubscriptionCreatedWebClientSubscriberUrlInvalid($webClientProperties)
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $user = $userService->getPublicUser();

        $logger = \Mockery::mock(LoggerInterface::class);
        $eventDispatcher = \Mockery::mock(EventDispatcherInterface::class);
        $stripeService = \Mockery::mock(StripeService::class);
        $stripeEventService = \Mockery::mock(StripeEventService::class);
        $userAccountPlanService = \Mockery::mock(UserAccountPlanService::class);
        $httpClientService = \Mockery::mock(HttpClientService::class);
        $accountPlanService = \Mockery::mock(AccountPlanService::class);

        $stripeEventService
            ->shouldReceive('getForUserAndType')
            ->andReturn([]);

        $stripeEventService
            ->shouldReceive('persistAndFlush');

        $listener = new CustomerSubscriptionCreatedListener(
            $logger,
            $eventDispatcher,
            $stripeService,
            $stripeEventService,
            $userAccountPlanService,
            $httpClientService,
            $accountPlanService,
            $webClientProperties
        );

        $stripeEvent = new StripeEvent();
        $stripeEvent->setUser($user);
        $stripeEvent->setStripeEventData(
            json_encode(StripeEventFixtureFactory::load('customer.subscription.created.active'))
        );

        $dispatchableEvent = new DispatchableEvent($stripeEvent);

        $listener->onCustomerSubscriptionCreated($dispatchableEvent);
    }

    /**
     * @return array
     */
    public function webClientPropertiesDataProvider()
    {
        return [
            'no urls' => [
                'webClientProperties' => [],
            ],
            'no base url' => [
                'webClientProperties' => [
                    'urls' => [],
                ],
            ],
            'no stripe event controller' => [
                'webClientProperties' => [
                    'urls' => [
                        'base' => 'http://example.com/',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider onCustomerSubscriptionCreatedDataProvider
     *
     * @param array $httpFixtures
     * @param array $stripeEventFixtures
     * @param string $userName
     * @param array $stripeServiceResponses
     * @param array $expectedWebClientRequestDataCollection
     */
    public function testOnCustomerSubscriptionCreated(
        $httpFixtures,
        $stripeEventFixtures,
        $userName,
        $stripeServiceResponses,
        $expectedWebClientRequestDataCollection
    ) {
        $eventDispatcher = $this->container->get('event_dispatcher');
        $httpClientService = $this->container->get('simplytestable.services.httpclientservice');
        $stripeService = $this->container->get('simplytestable.services.stripeservice');
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');

        foreach ($stripeServiceResponses as $methodName => $responseData) {
            $stripeService->addResponseData($methodName, $responseData);
        }

        $this->queueHttpFixtures($httpFixtures);

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
        $this->assertWebClientRequests($httpClientService, $expectedWebClientRequestDataCollection);
    }

    /**
     * @return array
     */
    public function onCustomerSubscriptionCreatedDataProvider()
    {
        return [
            'customer.subscription.created.active' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createSuccessResponse(),
                ],
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
                'expectedWebClientRequestDataCollection' => [
                    [
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
            ],
            'customer.subscription.created.trialing; active card' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createSuccessResponse(),
                ],
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
                'expectedWebClientRequestDataCollection' => [
                    [
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
            ],
            'customer.subscription.created.trialing; no card' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createSuccessResponse(),
                ],
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
                'expectedWebClientRequestDataCollection' => [
                    [
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
            ],
        ];
    }
}
