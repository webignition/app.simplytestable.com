<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionCreated\StatusTrialing;

class WithCardTest extends TrialingTest {

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

    protected function getHasCard() {
        return true;
    }
}