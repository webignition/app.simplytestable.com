<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionUpdated\StatusChange\TrialingToActive;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionUpdated\StatusChange\StatusChangeTest;

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
    
    protected function getStripeEventFixturePaths() {
        return array(
            $this->getFixturesDataPath() . '/../../StripeEvents/customer.subscription.updated.statuschange.json'
        );
    }      

}
