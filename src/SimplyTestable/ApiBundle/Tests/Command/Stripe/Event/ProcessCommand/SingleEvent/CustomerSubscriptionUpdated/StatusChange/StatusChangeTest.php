<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\CustomerSubscriptionUpdated\StatusChange;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\CustomerSubscriptionUpdated\CustomerSubscriptionUpdatedTest;

abstract class StatusChangeTest extends CustomerSubscriptionUpdatedTest {   
    
    abstract protected function getCurrentSubscriptionStatus();
    abstract protected function getPreviousSubscriptionStatus();   
    
    protected function getExpectedNotificationBodyFields() {
        return array_merge(parent::getExpectedNotificationBodyFields(), array(
            'is_status_change' => '1',
            'previous_subscription_status' => $this->getPreviousSubscriptionStatus(),
            'subscription_status' => $this->getCurrentSubscriptionStatus(),
            'plan_name' => 'Agency',
            'plan_amount' => '1900',
        ));
    }     

    protected function getStripeEventFixturePath() {
        return $this->getFixturesDataPath() . '/../StripeEvents/customer.subscription.updated.statuschange.json';
    }
    
    protected function getFixtureReplacements() {
        $fixtureReplacements = parent::getFixtureReplacements();
        $fixtureReplacements['{{subscription_status}}'] = $this->getCurrentSubscriptionStatus();
        $fixtureReplacements['{{previous_subscription_status}}'] = $this->getPreviousSubscriptionStatus();
        
        return $fixtureReplacements;
    }      
}
