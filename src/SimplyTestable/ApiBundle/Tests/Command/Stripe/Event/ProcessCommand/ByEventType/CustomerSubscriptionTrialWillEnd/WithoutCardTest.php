<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionTrialWillEnd;

class WithoutCardTest extends CustomerSubscriptionTrialWillEndTest {
    
    protected function getHasCard() {
        return false;
    }    
    

}
