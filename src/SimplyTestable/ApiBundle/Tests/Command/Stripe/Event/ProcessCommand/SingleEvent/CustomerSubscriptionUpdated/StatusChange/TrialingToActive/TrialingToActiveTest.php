<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\CustomerSubscriptionUpdated\StatusChange\TrialingToActive;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\CustomerSubscriptionUpdated\StatusChange\StatusChangeTest;

abstract class TrialingToActiveTest extends StatusChangeTest {   
    
    abstract protected function getHasCard();
    
    protected function getExpectedNotificationBodyFields() {
        return array_merge(parent::getExpectedNotificationBodyFields(), array(
            'has_card' => (int)$this->getHasCard(),
        ));
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
