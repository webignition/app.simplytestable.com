<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\CustomerSubscriptionUpdated\PlanChange;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\SingleEventTest;

abstract class PlanChangeTest extends SingleEventTest {   
    
    abstract protected function getSubscriptionStatus();
    
    public function testNotificationBodyEvent() {
        $this->assertNotificationBodyField('event', 'customer.subscription.updated');
    }
    
    public function testNotificationBodyUser() {
        $this->assertNotificationBodyField('user', 'user@example.com');
    }    
    
    public function testNotificationBodyIsPlanChange() {
        $this->assertNotificationBodyField('is_plan_change', '1');
    }      
    
    public function testNotificationBodyOldPlan() {
        $this->assertNotificationBodyField('old_plan', 'Personal');
    }
    
    public function testNotificationBodyNewPlan() {
        $this->assertNotificationBodyField('new_plan', 'Agency');
    }
    
    public function testNotificationBodyNewAmount() {
        $this->assertNotificationBodyField('new_amount', '1900');
    }
    
    public function testNotificationBodySubscriptionStatus() {
        $this->assertNotificationBodyField('subscription_status', $this->getSubscriptionStatus());
    }
    
    public function testNotificationBodyTrialEnd() {
        if ($this->getSubscriptionStatus() == 'trialing') {
            $this->assertNotificationBodyField('trial_end', '1405427088');
        }
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
