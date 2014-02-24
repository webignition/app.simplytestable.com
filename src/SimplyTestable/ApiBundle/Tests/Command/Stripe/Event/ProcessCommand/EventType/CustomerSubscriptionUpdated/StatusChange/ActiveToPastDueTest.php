<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\CustomerSubscriptionUpdated\StatusChange;

class ActiveToPastDueTest extends NoActionStatusTest {   

    protected function getCurrentSubscriptionStatus() {
        return 'past_due';
    }

    protected function getPreviousSubscriptionStatus() {
        return 'active';
    }
}
