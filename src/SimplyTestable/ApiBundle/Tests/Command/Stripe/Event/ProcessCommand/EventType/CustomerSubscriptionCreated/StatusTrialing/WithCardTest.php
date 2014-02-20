<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\CustomerSubscriptionCreated\StatusTrialing;

class WithCardTest extends TrialingTest {
   
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
