<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionTrialWillEnd\WithoutDiscount;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionTrialWillEnd\WithoutDiscountTest;

class WithoutCardTest extends WithoutDiscountTest {
    
    protected function getHasCard() {
        return false;
    }    
    

}
