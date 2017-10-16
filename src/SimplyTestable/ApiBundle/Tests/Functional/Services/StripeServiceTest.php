<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
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

    /**
     * @dataProvider getCustomerDataProvider
     *
     * @param string $userAccountPlanStripeCustomer
     */
    public function testGetCustomer($userAccountPlanStripeCustomer)
    {
        StripeApiFixtureFactory::set(
            StripeApiFixtureFactory::load('customer')
        );

        $userAccountPlan = new UserAccountPlan();
        $userAccountPlan->setStripeCustomer($userAccountPlanStripeCustomer);

        $customer = $this->stripeService->getCustomer($userAccountPlan);

        if (empty($userAccountPlanStripeCustomer)) {
            $this->assertNull($customer);
        } else {
            $this->assertInstanceOf(StripeCustomer::class, $customer);
        }
    }

    /**
     * @return array
     */
    public function getCustomerDataProvider()
    {
        return [
            'no stripe id' => [
                'userAccountPlanStripeCustomer' => null,
            ],
            'has stripe id' => [
                'userAccountPlanStripeCustomer' => 'cus_BanNZHjdfXcrAl',
            ],
        ];
    }


    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
