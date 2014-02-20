<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\CustomerSubscriptionUpdated\StatusChange;

class PastDueToCanceledTest extends ActionStatusTest {   
    
    protected function getExpectedWebClientEventBody() {
        return 'event=customer.subscription.updated&is_status_change=1&previous_subscription_status='.$this->getPreviousSubscriptionStatus().'&subscription_status=' . $this->getCurrentSubscriptionStatus();
    }

    protected function getCurrentSubscriptionStatus() {
        return 'canceled';
    }

    protected function getPreviousSubscriptionStatus() {
        return 'past_due';
    }

}
