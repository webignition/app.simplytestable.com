<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use SimplyTestable\ApiBundle\Services\StripeService;
use SimplyTestable\ApiBundle\Tests\Factory\StripeApiFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use webignition\Model\Stripe\Customer as StripeCustomerModel;

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
            [StripeApiFixtureFactory::load('customer-nocard-nosub')]
        );

        $user = new User();
        $user->setEmail('test-foo@example.com');

        $customer = $this->stripeService->createCustomer($user, $coupon);

        $this->assertInstanceOf(StripeCustomerModel::class, $customer);
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
            [StripeApiFixtureFactory::load('customer-nocard-nosub')]
        );

        $userAccountPlan = new UserAccountPlan();
        $userAccountPlan->setStripeCustomer($userAccountPlanStripeCustomer);

        $customer = $this->stripeService->getCustomer($userAccountPlan);

        if (empty($userAccountPlanStripeCustomer)) {
            $this->assertNull($customer);
        } else {
            $this->assertInstanceOf(StripeCustomerModel::class, $customer);
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
     * @dataProvider updateCustomerDataProvider
     *
     * @param $propertiesToUpdate
     */
    public function testUpdateCustomer($propertiesToUpdate)
    {
        StripeApiFixtureFactory::set([
            StripeApiFixtureFactory::load('customer-nocard-nosub'),
            StripeApiFixtureFactory::load('customer-nocard-nosub'),
        ]);

        $userAccountPlan = new UserAccountPlan();
        $userAccountPlan->setStripeCustomer('cus_BanNjUqqa6RWw9');

        $updatedCustomer = $this->stripeService->updateCustomer($userAccountPlan, $propertiesToUpdate);

        $this->assertInstanceOf(StripeCustomerModel::class, $updatedCustomer);
    }

    /**
     * @return array
     */
    public function updateCustomerDataProvider()
    {
        return [
            'add metadata' => [
                'propertiesToUpdate' => [
                    'metadata' => [
                        'foo' => 'bar',
                    ],
                ],
            ],
        ];
    }

    public function testSubscribe()
    {
        $accountPlanService = $this->container->get('simplytestable.services.accountplanservice');

        StripeApiFixtureFactory::set([
            StripeApiFixtureFactory::load('customer-nocard-nosub'),
            StripeApiFixtureFactory::load('customer-nocard-nosub'),
        ]);

        $userAccountPlan = new UserAccountPlan();
        $userAccountPlan->setStripeCustomer('cus_BanNjUqqa6RWw9');
        $userAccountPlan->setPlan($accountPlanService->find('personal'));

        $response = $this->stripeService->subscribe($userAccountPlan);

        $this->assertInstanceOf(UserAccountPlan::class, $response);
    }

    public function testUnsubscribeNoSubscription()
    {
        StripeApiFixtureFactory::set([
            StripeApiFixtureFactory::load('customer-nocard-nosub'),
        ]);

        $userAccountPlan = new UserAccountPlan();
        $userAccountPlan->setStripeCustomer('cus_BanNjUqqa6RWw9');

        $this->stripeService->unsubscribe($userAccountPlan);
    }

    public function testUnsubscribeHasSubscription()
    {
        StripeApiFixtureFactory::set([
            StripeApiFixtureFactory::load('customer-hascard-hassub'),
            StripeApiFixtureFactory::load('customer-hascard-nosub'),
        ]);

        $userAccountPlan = new UserAccountPlan();
        $userAccountPlan->setStripeCustomer('cus_BanNjUqqa6RWw9');

        $this->stripeService->unsubscribe($userAccountPlan);
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
