<?php

namespace App\Tests\Functional\EventListener\Stripe;

use GuzzleHttp\Psr7\Response;
use App\Event\Stripe\DispatchableEvent;
use App\Services\UserAccountPlanService;
use App\Tests\Factory\StripeApiFixtureFactory;
use App\Tests\Services\StripeEventFactory;
use App\Tests\Services\UserFactory;

class InvoicePaymentFailedListenerTest extends AbstractStripeEventListenerTest
{
    /**
     * @dataProvider onInvoicePaymentFailedDataProvider
     *
     * @param array $httpFixtures
     * @param array $stripeEventFixtures
     * @param string $userName
     * @param array $stripeApiHttpFixtures
     * @param array $expectedWebClientRequestDataCollection
     */
    public function testOnInvoicePaymentFailed(
        $httpFixtures,
        $stripeEventFixtures,
        $userName,
        $stripeApiHttpFixtures,
        $expectedWebClientRequestDataCollection
    ) {
        $eventDispatcher = self::$container->get('event_dispatcher');
        $userAccountPlanService = self::$container->get(UserAccountPlanService::class);

        StripeApiFixtureFactory::set($stripeApiHttpFixtures);

        $this->httpClientService->appendFixtures($httpFixtures);

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
    }

    /**
     * @return array
     */
    public function onInvoicePaymentFailedDataProvider()
    {
        $successResponse = new Response();

        return [
            'invoice.payment_failed; no card' => [
                'httpFixtures' => [
                    $successResponse,
                ],
                'stripeEventFixtures' => [
                    'invoice.payment_failed' => [],
                ],
                'user' => 'public',
                'stripeApiHttpFixtures' => [
                    StripeApiFixtureFactory::load('customer-nocard-hassub'),
                ],
                'expectedWebClientRequestDataCollection' => [],
            ],
            'invoice.payment_failed; has card' => [
                'httpFixtures' => [
                    $successResponse,
                ],
                'stripeEventFixtures' => [
                    'invoice.payment_failed' => [
                        'data' => [
                            'object' => [
                                'id' => 'in_invoiceId',
                                'total' => 2500,
                                'amount_due' => 2600,
                                'currency' => 'eur',
                                'lines' => [
                                    'data' => [
                                        [
                                            'proration' => true,
                                            'amount' => 2500,
                                            'period' => [
                                                'start' => 1,
                                                'end' => 2,
                                            ],
                                            'plan' => [
                                                'name' => 'Plan Name',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]
                ],
                'user' => 'public',
                'stripeApiHttpFixtures' => [
                    StripeApiFixtureFactory::load('customer-hascard-hassub'),
                ],
                'expectedWebClientRequestDataCollection' => [
                    [
                        'event' =>  'invoice.payment_failed',
                        'user' => 'public@simplytestable.com',
                        'lines' => [
                            [
                                'proration' => 1,
                                'plan_name' => 'Plan Name',
                                'period_start' => 1,
                                'period_end' => 2,
                                'amount' => 2500,
                            ],
                        ],
                        'invoice_id' => 'in_invoiceId',
                        'total' => 2500,
                        'amount_due' => 2600,
                        'currency' => 'eur',
                    ]
                ],
            ],
            'invoice.payment_failed; no invoice lines' => [
                'httpFixtures' => [
                    $successResponse,
                ],
                'stripeEventFixtures' => [
                    'invoice.payment_failed.no_lines' => [
                        'data' => [
                            'object' => [
                                'id' => 'in_invoiceId',
                                'total' => 2500,
                                'amount_due' => 2600,
                                'currency' => 'eur',
                            ],
                        ],
                    ]
                ],
                'user' => 'public',
                'stripeApiHttpFixtures' => [
                    StripeApiFixtureFactory::load('customer-hascard-hassub'),
                ],
                'expectedWebClientRequestDataCollection' => [
                    [
                        'event' =>  'invoice.payment_failed',
                        'user' => 'public@simplytestable.com',
                        'invoice_id' => 'in_invoiceId',
                        'total' => 2500,
                        'amount_due' => 2600,
                        'currency' => 'eur',
                    ],
                ],
            ],
            'invoice.payment_failed; received after customer.subscription.deleted event' => [
                'httpFixtures' => [
                    $successResponse,
                    $successResponse,
                ],
                'stripeEventFixtures' => [
                    'customer.subscription.deleted' => [
                        'data' => [
                            'object' => [
                                'plan' => [
                                    'name' => 'Plan Name',
                                    'amount' => 10000,
                                    'currency' => 'eur'
                                ],
                                'trial_start' => 1,
                                'trial_end' => 4,
                                'canceled_at' => 5,
                            ],
                        ],
                    ],
                    'invoice.payment_failed' => [
                        'data' => [
                            'object' => [
                                'id' => 'in_invoiceId',
                                'total' => 2500,
                                'amount_due' => 2600,
                                'currency' => 'eur',
                                'lines' => [
                                    'data' => [
                                        [
                                            'proration' => true,
                                            'amount' => 2500,
                                            'period' => [
                                                'start' => 1,
                                                'end' => 2,
                                            ],
                                            'plan' => [
                                                'name' => 'Plan Name',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]
                ],
                'user' => 'public',
                'stripeApiHttpFixtures' => [
                    StripeApiFixtureFactory::load('customer-hascard-hassub'),
                ],
                'expectedWebClientRequestDataCollection' => [
                    [
                        'event' =>  'invoice.payment_failed',
                        'user' => 'public@simplytestable.com',
                        'lines' => [
                            [
                                'proration' => 1,
                                'plan_name' => 'Plan Name',
                                'period_start' => 1,
                                'period_end' => 2,
                                'amount' => 2500,
                            ],
                        ],
                        'invoice_id' => 'in_invoiceId',
                        'total' => 2500,
                        'amount_due' => 2600,
                        'currency' => 'eur',
                    ],
                    [
                        'event' => 'customer.subscription.deleted',
                        'user' => 'public@simplytestable.com',
                        'plan_name' => 'Plan Name',
                        'actioned_by' => 'system',
                    ],
                ],
            ],
            'invoice.payment_failed; no matching customer.subscription.deleted event' => [
                'httpFixtures' => [
                    $successResponse,
                ],
                'stripeEventFixtures' => [
                    'customer.subscription.deleted' => [
                        'data' => [
                            'object' => [
                                'id' => 'non-matching',
                            ],
                        ],
                    ],
                    'invoice.payment_failed' => [
                        'data' => [
                            'object' => [
                                'id' => 'in_invoiceId',
                                'total' => 2500,
                                'amount_due' => 2600,
                                'currency' => 'eur',
                                'lines' => [
                                    'data' => [
                                        [
                                            'proration' => true,
                                            'amount' => 2500,
                                            'period' => [
                                                'start' => 1,
                                                'end' => 2,
                                            ],
                                            'plan' => [
                                                'name' => 'Plan Name',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]
                ],
                'user' => 'public',
                'stripeApiHttpFixtures' => [
                    StripeApiFixtureFactory::load('customer-hascard-hassub'),
                ],
                'expectedWebClientRequestDataCollection' => [
                    [
                        'event' =>  'invoice.payment_failed',
                        'user' => 'public@simplytestable.com',
                        'lines' => [
                            [
                                'proration' => 1,
                                'plan_name' => 'Plan Name',
                                'period_start' => 1,
                                'period_end' => 2,
                                'amount' => 2500,
                            ],
                        ],
                        'invoice_id' => 'in_invoiceId',
                        'total' => 2500,
                        'amount_due' => 2600,
                        'currency' => 'eur',
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
