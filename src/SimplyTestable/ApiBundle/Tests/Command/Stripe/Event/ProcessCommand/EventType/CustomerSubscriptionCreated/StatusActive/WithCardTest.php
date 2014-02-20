<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\CustomerSubscriptionCreated\StatusActive;

class WithCardTest extends ActiveTest {
    
    protected function getStripeServiceResponseMethod() {
        return 'getCustomer';
    }
    
    protected function getStripeServiceResponseData() {
        return array(
            'active_card' => '123'
        );
    }

    protected function getHasCard() {
        return true;
    }    
}
