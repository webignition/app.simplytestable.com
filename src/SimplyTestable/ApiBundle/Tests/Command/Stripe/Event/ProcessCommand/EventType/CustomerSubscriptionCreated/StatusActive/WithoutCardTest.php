<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\CustomerSubscriptionCreated\StatusActive;

class WithoutCardTest extends ActiveTest {

    protected function getHasCard() {
        return false;
    }        
}
