<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\CustomerSubscriptionUpdated\StatusChange;

class ActiveToCanceledTest extends StatusChangeTest {      

    protected function getCurrentSubscriptionStatus() {
        return 'canceled';
    }

    protected function getPreviousSubscriptionStatus() {
        return 'active';
    }

}
