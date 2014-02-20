<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\CustomerSubscriptionUpdated\StatusChange\TrialingToActive;

class WithoutCardTest extends TrialingToActiveTest {   

    protected function getHasCard() {
        return false;
    }    

}
