<?php

namespace App\Tests\Functional\EventListener\Stripe;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Response;
use Mockery\Mock;
use App\Event\Stripe\DispatchableEvent;
use App\EventListener\Stripe\CustomerSubscriptionCreatedListener;
use App\Services\StripeEventService;
use App\Services\StripeService;
use App\Services\UserAccountPlanService;
use App\Services\UserService;
use App\Tests\Factory\ConnectExceptionFactory;
use App\Tests\Factory\StripeApiFixtureFactory;
use App\Tests\Factory\StripeEventFactory;
use App\Tests\Factory\StripeEventFixtureFactory;
use App\Tests\Factory\UserFactory;
use App\Entity\Stripe\Event as StripeEvent;

class CustomerSubscriptionCreatedListenerTest extends AbstractStripeEventListenerTest
{
    public function testOnCustomerSubscriptionCreatedWebClientRequestFailure()
    {
        $eventDispatcher = self::$container->get('event_dispatcher');
        $userAccountPlanService = self::$container->get(UserAccountPlanService::class);
        $userService = self::$container->get(UserService::class);

        $this->httpClientService->appendFixtures([
            ConnectExceptionFactory::create('CURL/28 Operation timed out'),
        ]);

        $user = $userService->getPublicUser();

        $userAccountPlan = $userAccountPlanService->getForUser($user);
        $userAccountPlan->setStripeCustomer('non-empty value');

        $stripeEventFactory = new StripeEventFactory(self::$container);

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
        $userService = self::$container->get(UserService::class);
        $user = $userService->getPublicUser();

        /* @var Mock|StripeService $stripeService */
        $stripeService = \Mockery::mock(StripeService::class);

        /* @var Mock|StripeEventService $stripeEventService */
        $stripeEventService = \Mockery::mock(StripeEventService::class);

        /* @var Mock|UserAccountPlanService $userAccountPlanService */
        $userAccountPlanService = \Mockery::mock(UserAccountPlanService::class);

        /* @var Mock|HttpClient $httpClient */
        $httpClient = \Mockery::mock(HttpClient::class);

        /* @var Mock|EntityManagerInterface $entityManager */
        $entityManager = \Mockery::mock(EntityManagerInterface::class);

        $entityManager
            ->shouldReceive('persist');

        $entityManager
            ->shouldReceive('flush');

        $stripeEventService
            ->shouldReceive('getForUserAndType')
            ->andReturn([]);

        $listener = new CustomerSubscriptionCreatedListener(
            $stripeEventService,
            $httpClient,
            $entityManager,
            $webClientProperties,
            $stripeService,
            $userAccountPlanService
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
     * @param array $stripeEventFixtures
     * @param string $userName
     * @param string[] $stripeApiHttpFixtures
     * @param array $expectedWebClientRequestDataCollection
     */
    public function testOnCustomerSubscriptionCreated(
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
                'stripeApiHttpFixtures' => [],
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
                'stripeApiHttpFixtures' => [
                    StripeApiFixtureFactory::load('customer-hascard-hassub'),
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
                'stripeApiHttpFixtures' => [
                    StripeApiFixtureFactory::load('customer-nocard-nosub'),
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

    protected function tearDown()
    {
        parent::tearDown();

        $this->addToAssertionCount(\Mockery::getContainer()->mockery_getExpectationCount());

        \Mockery::close();
    }
}
