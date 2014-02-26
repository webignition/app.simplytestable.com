<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\CustomerSubscriptionCreated;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\SingleEventTest;

abstract class CustomerSubscriptionCreatedTest extends SingleEventTest {   
    
    abstract protected function getSubscriptionStatus();
    abstract protected function getHasCard();
    
    protected function getHttpFixtureItems() {
        return array(
            "HTTP/1.1 200 OK"
        );
    }
    
    
    public function testNotificationBodyEvent() {        
        $this->assertNotificationBodyField('event', 'customer.subscription.created');
    }
    
    public function testNotificationBodyUser() {
        $this->assertNotificationBodyField('user', 'user@example.com');
    }
    
    public function testNotificationBodyStatus() {
        $this->assertNotificationBodyField('status', $this->getSubscriptionStatus());
    }
    
    public function testNotificationBodyPlanName() {
        $this->assertNotificationBodyField('plan_name', 'Basic');
    }    
    
    public function testWebClientSubscriberResponseStatusCode() {        
        $this->assertEquals(
                200,
                $this->getHttpClientService()->getHistoryPlugin()->getLastResponse()->getStatusCode()
        );
    }      
}
