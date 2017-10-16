<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\StripeService;
use SimplyTestable\ApiBundle\Tests\Factory\StripeApiFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Stripe\Customer as StripeCustomer;

class StripeServiceTest extends BaseSimplyTestableTestCase
{
    /**
     * @var StripeService
     */
    private $stripeService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->stripeService = new StripeService(
            $this->container->getParameter('stripe_key')
        );
    }

    /**
     * @dataProvider createCustomerDataProvider
     *
     * @param string $coupon
     */
    public function testCreateCustomer($coupon)
    {
        StripeApiFixtureFactory::set(
            StripeApiFixtureFactory::load('customer')
        );

        $user = new User();
        $user->setEmail('test-foo@example.com');

        $customer = $this->stripeService->createCustomer($user, $coupon);

        $this->assertInstanceOf(StripeCustomer::class, $customer);
    }

    /**
     * @return array
     */
    public function createCustomerDataProvider()
    {
        return [
            'no coupon' => [
                'coupon' => null,
            ],
            'has coupon' => [
                'coupon' => 'foo',
            ],
        ];
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
