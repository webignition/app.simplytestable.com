<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\CustomerSubscriptionUpdated\StatusChange;

class TrialingToCanceledTest extends ActionStatusTest {   
    
    protected function getExpectedWebClientEventBody() {
        return 'event=customer.subscription.updated&user=user%40example.com&is_status_change=1&previous_subscription_status='.$this->getPreviousSubscriptionStatus().'&subscription_status=' . $this->getCurrentSubscriptionStatus() . '&trial_days_remaining=30';
    }     

    protected function getCurrentSubscriptionStatus() {
        return 'canceled';
    }

    protected function getPreviousSubscriptionStatus() {
        return 'trialing';
    }

}
