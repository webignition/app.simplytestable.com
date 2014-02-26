<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\CustomerSubscriptionUpdated\StatusChange;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\SingleEventTest;

abstract class StatusChangeTest extends SingleEventTest {   
    
    abstract protected function getCurrentSubscriptionStatus();
    abstract protected function getPreviousSubscriptionStatus();   

    protected function getStripeEventFixturePath() {
        return $this->getFixturesDataPath() . '/../StripeEvents/customer.subscription.updated.statuschange.json';
    }
    
    protected function getFixtureReplacements() {
        $fixtureReplacements = parent::getFixtureReplacements();
        $fixtureReplacements['{{subscription_status}}'] = $this->getCurrentSubscriptionStatus();
        $fixtureReplacements['{{previous_subscription_status}}'] = $this->getPreviousSubscriptionStatus();
        
        return $fixtureReplacements;
    }  
    
    protected function getHttpFixtureItems() {
        return array(
            "HTTP/1.1 200 OK"
        );
    }     
}
