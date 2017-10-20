<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Adapter\Stripe\Customer;

use SimplyTestable\ApiBundle\Tests\Factory\ModelFactory;
use SimplyTestable\ApiBundle\Tests\Factory\StripeApiFixtureFactory;
use SimplyTestable\ApiBundle\Adapter\Stripe\Customer\StripeCustomerAdapter;
use webignition\Model\Stripe\Customer as StripeCustomerModel;

class StripeCustomerAdapterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var StripeCustomerAdapter
     */
    private $stripeCustomerAdapter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->stripeCustomerAdapter = new StripeCustomerAdapter();
    }

    /**
     * @dataProvider getStripeCustomerDataProvider
     *
     * @param string $stripeCustomerId
     * @param string $fixtureName
     * @param array $fixtureReplacements
     * @param array $fixtureModifications
     * @param array $expectedStripeCustomerModelArray
     */
    public function testGetStripeCustomer(
        $stripeCustomerId,
        $fixtureName,
        $fixtureReplacements,
        $fixtureModifications,
        $expectedStripeCustomerModelArray
    ) {
        $stripeCustomer = ModelFactory::createStripeCustomer(
            $stripeCustomerId,
            StripeApiFixtureFactory::load($fixtureName, $fixtureReplacements, $fixtureModifications)
        );

        $stripeCustomerModel = $this->stripeCustomerAdapter->getStripeCustomer($stripeCustomer);

        $this->assertInstanceOf(StripeCustomerModel::class, $stripeCustomerModel);

        $stripeCustomerModelArray = $stripeCustomerModel->__toArray();

        foreach ($expectedStripeCustomerModelArray as $expectedKey => $expectedValue) {
            $this->assertArrayHasKey($expectedKey, $stripeCustomerModelArray);

            if (is_scalar($expectedValue)) {
                $this->assertEquals($expectedValue, $stripeCustomerModelArray[$expectedKey]);
            }


            if (is_array($expectedValue)) {
                $comparatorValue = $stripeCustomerModelArray[$expectedKey];

                foreach ($expectedValue as $expectedValueKey => $expectedValueValue) {
                    $this->assertArrayHasKey($expectedValueKey, $comparatorValue);
                    $this->assertEquals($expectedValueValue, $comparatorValue[$expectedValueKey]);
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getStripeCustomerDataProvider()
    {
        return [
            'no card no sub' => [
                'stripeCustomerId' => 'cus_BanNZHjdfXcrAl',
                'fixtureName' => 'customer-nocard-nosub',
                'fixtureReplacements' => [
                    '{stripe-customer-id}' => 'cus_BanNZHjdfXcrAl',
                ],
                'fixtureModifications' => [
                    'id' => 'cus_BanNZHjdfXcrAl',
                    'email' => 'nocard-nosub@example.com',
                ],
                'expectedStripeCustomerModelArray' => [
                    'id' => 'cus_BanNZHjdfXcrAl',
                    'object' => 'customer',
                    'currency' => null,
                    'default_source' => null,
                    'description' => 'No card no sub',
                    'discount' => null,
                    'email' => 'nocard-nosub@example.com',
                    'default_card' => null,
                    'subscription' => null,
                    'active_card' => null,
                ],
            ],
            'has card no sub' => [
                'stripeCustomerId' => 'cus_5tqX3AjeYpO7Ow',
                'fixtureName' => 'customer-hascard-nosub',
                'fixtureReplacements' => [
                    '{stripe-customer-id}' => 'cus_5tqX3AjeYpO7Ow',
                ],
                'fixtureModifications' => [
                    'id' => 'cus_5tqX3AjeYpO7Ow',
                    'email' => 'hascard-nosub@example.com',
                ],
                'expectedStripeCustomerModelArray' => [
                    'id' => 'cus_5tqX3AjeYpO7Ow',
                    'object' => 'customer',
                    'currency' => 'gbp',
                    'default_source' => 'card_Bb49mNCO7Q6no2',
                    'description' => 'Has card no sub',
                    'discount' => null,
                    'email' => 'hascard-nosub@example.com',
                    'default_card' => 'card_Bb49mNCO7Q6no2',
                    'subscription' => null,
                    'active_card' => [
                        'id' => 'card_Bb49mNCO7Q6no2',
                        'object' => 'card',
                        'customer' => 'cus_5tqX3AjeYpO7Ow',
                        'exp_month' => 12,
                        'exp_year' => 2022,
                        'last4' => '4242',
                    ],
                ],
            ],
            'has card has sub' => [
                'stripeCustomerId' => 'cus_5u8wX9CO94WJO2',
                'fixtureName' => 'customer-hascard-hassub',
                'fixtureReplacements' => [
                    '{stripe-customer-id}' => 'cus_5u8wX9CO94WJO2',
                ],
                'fixtureModifications' => [
                    'id' => 'cus_5u8wX9CO94WJO2',
                    'email' => 'hascard-hassub@example.com',
                ],
                'expectedStripeCustomerModelArray' => [
                    'id' => 'cus_5u8wX9CO94WJO2',
                    'object' => 'customer',
                    'currency' => 'gbp',
                    'default_source' => 'card_Bb4A2szGLfgwJe',
                    'description' => 'Has card has sub',
                    'discount' => null,
                    'email' => 'hascard-hassub@example.com',
                    'default_card' => 'card_Bb4A2szGLfgwJe',
                    'subscription' => [
                        'id' => 'sub_Bb4AOCnRZzV80I',
                        'object' => 'subscription',
                        'billing' => 'charge_automatically',
                        'cancel_at_period_end' => false,
                        'canceled_at' => null,
                        'current_period_end' => 1510827068,
                        'current_period_start' => 1508235068,
                        'customer' => 'cus_5u8wX9CO94WJO2',
                        'discount' => null,
                        'ended_at' => null,
                        'plan' => [
                            'id' => 'personal-9',
                            'object' => 'plan',
                            'amount' => 900,
                            'created' => 1370600314,
                            'currency' => 'gbp',
                            'interval' => 'month',
                            'interval_count' => 1,
                            'livemode' => false,
                            'metadata' => [],
                            'name' => 'Personal',
                            'statement_descriptor' => null,
                            'trial_period_days' => 30,
                            'statement_description' => null,
                        ],
                        'quantity' => 1,
                        'start' => 1508235068,
                        'status' => 'trialing',
                        'trial_end' => 1510827068,
                        'trial_start' => 1508235068,
                    ],
                    'active_card' => [
                        'id' => 'card_Bb4A2szGLfgwJe',
                        'object' => 'card',
                        'customer' => 'cus_5u8wX9CO94WJO2',
                        'exp_month' => 12,
                        'exp_year' => 2022,
                        'last4' => '4242',
                    ],
                ],
            ],
            'has card has sub has coupon' => [
                'stripeCustomerId' => 'cus_Bb4O5V1GrdPc2k',
                'fixtureName' => 'customer-hascard-hassub-hascoupon',
                'fixtureReplacements' => [
                    '{stripe-customer-id}' => 'cus_Bb4O5V1GrdPc2k',
                ],
                'fixtureModifications' => [
                    'id' => 'cus_Bb4O5V1GrdPc2k',
                    'email' => 'hascard-hassub-hascoupon@example.com',
                ],
                'expectedStripeCustomerModelArray' => [
                    'id' => 'cus_Bb4O5V1GrdPc2k',
                    'object' => 'customer',
                    'currency' => 'gbp',
                    'default_source' => 'card_Bb4OGBPXSBoscP',
                    'description' => 'Has card has sub has coupon',
                    'discount' => null,
                    'email' => 'hascard-hassub-hascoupon@example.com',
                    'livemode' => false,
                    'metadata' => [],
                    'shipping' => null,
                    'default_card' => 'card_Bb4OGBPXSBoscP',
                    'subscription' => [
                        'id' => 'sub_Bb4OHpIp7R8f5d',
                        'object' => 'subscription',
                        'application_fee_percent' => null,
                        'billing' => 'charge_automatically',
                        'cancel_at_period_end' => false,
                        'canceled_at' => null,
                        'created' => 1508235916,
                        'current_period_end' => 1510827916,
                        'current_period_start' => 1508235916,
                        'customer' => 'cus_Bb4O5V1GrdPc2k',
                        'ended_at' => null,
                        'livemode' => false,
                        'metadata' => [],
                        'plan' => [
                            'id' => 'agency-19',
                            'object' => 'plan',
                            'amount' => 1900,
                            'created' => 1370600333,
                            'currency' => 'gbp',
                            'interval' => 'month',
                            'interval_count' => 1,
                            'livemode' => false,
                            'metadata' => [],
                            'name' => 'Agency',
                            'statement_descriptor' => null,
                            'trial_period_days' => 30,
                            'statement_description' => null,
                        ],
                        'quantity' => 1,
                        'start' => 1508235916,
                        'status' => 'trialing',
                        'tax_percent' => null,
                        'trial_end' => 1510827916,
                        'trial_start' => 1508235916,
                    ],
                    'active_card' => [
                        'id' => 'card_Bb4OGBPXSBoscP',
                        'object' => 'card',
                        'customer' => 'cus_Bb4O5V1GrdPc2k',
                        'exp_month' => 11,
                        'exp_year' => 2034,
                        'last4' => '4242',
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