<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\CustomerSubscriptionUpdated\StatusChange;

class ActiveToPastDueTest extends NoActionStatusTest {   

    protected function getExpectedWebClientEventBody() {
        return 'event=customer.subscription.updated&is_status_change=1&previous_subscription_status='.$this->getPreviousSubscriptionStatus().'&subscription_status=' . $this->getCurrentSubscriptionStatus();
    }        
    
    protected function getCurrentSubscriptionStatus() {
        return 'past_due';
    }

    protected function getPreviousSubscriptionStatus() {
        return 'active';
    }
}
