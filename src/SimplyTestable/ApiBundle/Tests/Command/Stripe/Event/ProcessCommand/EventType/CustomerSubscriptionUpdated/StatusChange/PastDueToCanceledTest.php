<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\CustomerSubscriptionUpdated\StatusChange;

class PastDueToCanceledTest extends ActionStatusTest {   

    protected function getCurrentSubscriptionStatus() {
        return 'canceled';
    }

    protected function getPreviousSubscriptionStatus() {
        return 'past_due';
    }

}
