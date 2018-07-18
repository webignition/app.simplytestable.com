<?php

namespace App\Tests\Unit\User\Summary;

use App\Model\User\Summary\StripeCustomer as StripeCustomerSummary;
use App\Tests\Factory\ModelFactory;
use App\Tests\Factory\StripeApiFixtureFactory;
use webignition\Model\Stripe\Customer as StripeCustomerModel;

class StripeCustomerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param StripeCustomerModel $stripeCustomerModel
     * @param array $expectedReturnValue
     */
    public function testJsonSerialize(
        $stripeCustomerModel,
        $expectedReturnValue
    ) {
        $stripeCustomerSummary = new StripeCustomerSummary($stripeCustomerModel);

        $serializedStripeCustomerSummary = $stripeCustomerSummary->jsonSerialize();

        unset($serializedStripeCustomerSummary['sources']);

        $this->assertEquals($expectedReturnValue, $serializedStripeCustomerSummary);
    }

    /**
     * @return array
     */
    public function jsonSerializeDataProvider()
    {
        return [
            'no stripe customer model' => [
                'stripeCustomerModel' => null,
                'expectedReturnValue' => [],
            ],
            'has stripe customer model' => [
                'stripeCustomerModel' => ModelFactory::createStripeCustomerModel(
                    'customer-nocard-nosub',
                    [
                        '{stripe-customer-id}' => 'cus_aaaaaaaaaaaaa0',
                        '{stripe-customer-email}' => 'user@example.com',
                    ]
                ),
                'expectedReturnValue' => [
                    'id' => 'cus_aaaaaaaaaaaaa0',
                    'object' => 'customer',
                    'account_balance' => 0,
                    'created' => 1508172583,
                    'currency' => null,
                    'default_source' => null,
                    'delinquent' => false,
                    'description' => 'No card no sub',
                    'discount' => null,
                    'email' => 'user@example.com',
                    'livemode' => false,
                    'metadata' => [],
                    'shipping' => null,
                    'subscriptions' => [
                        'object' => 'list',
                        'data' => [],
                        'has_more' => false,
                        'total_count' => 0,
                        'url' => '/v1/customers/cus_aaaaaaaaaaaaa0/subscriptions',
                        'count' => 0,
                    ],
                    'cards' => [
                        'object' => 'list',
                        'data' => [],
                        'has_more' => false,
                        'total_count' => 0,
                        'url' => '/v1/customers/cus_aaaaaaaaaaaaa0/cards',
                        'count' => 0,
                    ],
                    'default_card' => null,
                    'subscription' => null,
                    'active_card' => null,
                ],
            ],
        ];
    }
}
