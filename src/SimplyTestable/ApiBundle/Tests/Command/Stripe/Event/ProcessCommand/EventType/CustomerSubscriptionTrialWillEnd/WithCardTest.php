<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\CustomerSubscriptionTrialWillEnd;

class WithCardTest extends CustomerSubscriptionTrialWillEndTest {
    
    protected function getHasCard() {
        return true;
    }    
    
    protected function getStripeServiceResponseMethod() {
        return 'getCustomer';
    }
    
    protected function getStripeServiceResponseData() {
        return array(
            'active_card' => '123'
        );
    }
}
