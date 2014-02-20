<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\CustomerSubscriptionUpdated\PlanChange;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\EventTypeTest;

abstract class PlanChangeTest extends EventTypeTest {   
    
    abstract protected function getSubscriptionStatus();
    
    protected function getExpectedWebClientEventBody() {
        return 'event=customer.subscription.updated&is_plan_change=1&old_plan=Personal&new_plan=Agency&new_amount=1900&subscription_status=' . $this->getSubscriptionStatus();
    }

    public function testWebClientEventBody() {        
        $this->assertEquals(
                $this->getExpectedWebClientEventBody(),
                (string)$this->getHttpClientService()->getHistoryPlugin()->getLastRequest()->getPostFields()
        );
    }    
    
    public function testWebClientSubscriberResponseStatusCode() {        
        $this->assertEquals(
                200,
                $this->getHttpClientService()->getHistoryPlugin()->getLastResponse()->getStatusCode()
        );
    }    

    protected function getHttpFixtureItems() {
        return array(
            "HTTP/1.1 200 OK"
        );
    }

    protected function getStripeEventFixturePath() {
        return $this->getFixturesDataPath() . '/../../StripeEvents/customer.subscription.updated.planchange.json';
    }
    
    protected function getFixtureReplacements() {
        $fixtureReplacements = parent::getFixtureReplacements();
        $fixtureReplacements['{{subscription_status}}'] = $this->getSubscriptionStatus();
        
        return $fixtureReplacements;
    }
    
}
