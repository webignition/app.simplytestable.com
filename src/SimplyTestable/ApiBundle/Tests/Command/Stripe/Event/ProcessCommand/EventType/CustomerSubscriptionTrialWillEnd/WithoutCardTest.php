<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\CustomerSubscriptionTrialWillEnd;

class WithoutCardTest extends CustomerSubscriptionTrialWillEndTest {
    
    protected function getHasCard() {
        return false;
    }    
    

}
