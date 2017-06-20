<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionTrialWillEnd\WithDiscount;

use SimplyTestable\ApiBundle\Tests\Functional\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionTrialWillEnd\WithDiscountTest;

class WithCardTest extends WithDiscountTest {

    protected function getHasCard() {
        return true;
    }

    protected function getStripeServiceResponseMethod() {
        return 'getCustomer';
    }

    protected function getStripeServiceResponseData() {
        return array(
            'active_card' => array(
                'exp_month' => '01',
                'exp_year' => '99',
                'last4' => '1234',
                'type' => 'Foo'
            )
        );
    }

}
