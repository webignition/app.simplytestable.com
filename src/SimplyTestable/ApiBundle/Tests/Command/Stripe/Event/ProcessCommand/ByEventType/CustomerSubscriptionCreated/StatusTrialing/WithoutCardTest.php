<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionCreated\StatusTrialing;

class WithoutCardTest extends TrialingTest {
    
    protected function getHasCard() {
        return false;
    }      
}
