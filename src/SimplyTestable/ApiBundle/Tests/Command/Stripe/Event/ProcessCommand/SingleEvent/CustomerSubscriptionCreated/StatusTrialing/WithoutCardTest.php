<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\CustomerSubscriptionCreated\StatusTrialing;

class WithoutCardTest extends TrialingTest {
    
    protected function getHasCard() {
        return false;
    }      
}
