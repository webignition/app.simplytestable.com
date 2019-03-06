<?php

namespace App\Tests\Functional\EventListener\Stripe;

use GuzzleHttp\Psr7\Response;
use App\Event\Stripe\DispatchableEvent;
use App\Services\UserAccountPlanService;
use App\Tests\Services\StripeEventFactory;
use App\Tests\Services\UserFactory;

class InvoicePaymentSucceededListenerTest extends AbstractStripeEventListenerTest
{
    /**
     * @dataProvider onInvoicePaymentSucceededDataProvider
     *
     * @param array $stripeEventFixtures
     * @param string $userName
     * @param array $expectedWebClientRequestDataCollection
     */
    public function testOnInvoicePaymentSucceeded(
        $stripeEventFixtures,
        $userName,
        $expectedWebClientRequestDataCollection
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
    }

    /**
     * @return array
     */
    public function onInvoicePaymentSucceededDataProvider()
    {
        return [
            'invoice.payment_succeeded; zero amount' => [
                'stripeEventFixtures' => [
                    'invoice.payment_succeeded' => [
                        'data' => [
                            'object' => [
                                'total' => 0,
                                'amount_due' => 0,
                            ],
                        ],
                    ]
                ],
                'user' => 'public',
                'expectedWebClientRequestDataCollection' => [],
            ],
            'invoice.payment_succeeded; no discount' => [
                'stripeEventFixtures' => [
                    'invoice.payment_succeeded' => [
                        'data' => [
                            'object' => [
                                'id' => 'in_invoiceId',
                                'total' => 2500,
                                'subtotal' => 2600,
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
                'expectedWebClientRequestDataCollection' => [
                    [
                        'event' =>  'invoice.payment_succeeded',
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
                        'subtotal' => '2600',
                        'total' => '2500',
                        'invoice_id' => 'in_invoiceId',
                        'amount_due' => '2600',
                        'currency' => 'eur',
                        'has_discount' => 0,
                    ]
                ],
            ],
            'invoice.payment_succeeded; has discount' => [
                'stripeEventFixtures' => [
                    'invoice.payment_succeeded.discount' => [
                        'data' => [
                            'object' => [
                                'id' => 'in_invoiceId',
                                'total' => 2500,
                                'subtotal' => 2600,
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
                                'discount' => [
                                    'coupon' => [
                                        'id' => 'Coupon ID',
                                        'percent_off' => 10,
                                    ],
                                ],
                            ],
                        ],
                    ]
                ],
                'user' => 'private',
                'expectedWebClientRequestDataCollection' => [
                    [
                        'event' =>  'invoice.payment_succeeded',
                        'user' => 'private@example.com',
                        'lines' => [
                            [
                                'proration' => 1,
                                'plan_name' => 'Plan Name',
                                'period_start' => 1,
                                'period_end' => 2,
                                'amount' => 2500,
                            ],
                        ],
                        'subtotal' => '2600',
                        'total' => '2500',
                        'invoice_id' => 'in_invoiceId',
                        'amount_due' => '2600',
                        'currency' => 'eur',
                        'has_discount' => 1,
                        'discount' => [
                            'coupon' => 'Coupon ID',
                            'percent_off' => 10,
                            'discount' => 260,
                        ],
                    ],
                ],
            ],
        ];
    }
}
