<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\CustomerSubscriptionUpdated\StatusChange\TrialingToActive;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\CustomerSubscriptionUpdated\StatusChange\ActionStatusTest;

abstract class TrialingToActiveTest extends ActionStatusTest {   
    
    abstract protected function getHasCard();
    
    protected function getExpectedWebClientEventBody() {
        return 'event=customer.subscription.updated&is_status_change=1&previous_subscription_status='.$this->getPreviousSubscriptionStatus().'&subscription_status=' . $this->getCurrentSubscriptionStatus() . '&has_card=' . ((int)$this->getHasCard());
    }     

    protected function getCurrentSubscriptionStatus() {
        return 'active';
    }

    protected function getPreviousSubscriptionStatus() {
        return 'trialing';
    }
    
    protected function getStripeEventFixturePath() {
        return $this->getFixturesDataPath() . '/../../StripeEvents/customer.subscription.updated.statuschange.json';
    }    

}
