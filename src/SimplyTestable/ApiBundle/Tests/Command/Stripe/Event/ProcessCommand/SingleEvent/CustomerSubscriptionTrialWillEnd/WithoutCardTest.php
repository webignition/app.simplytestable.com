<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\CustomerSubscriptionTrialWillEnd;

class WithoutCardTest extends CustomerSubscriptionTrialWillEndTest {
    
    protected function getHasCard() {
        return false;
    }    
    

}
