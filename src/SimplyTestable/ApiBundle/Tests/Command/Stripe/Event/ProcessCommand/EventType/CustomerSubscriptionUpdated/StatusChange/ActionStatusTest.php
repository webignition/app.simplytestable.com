<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\CustomerSubscriptionUpdated\StatusChange;

abstract class ActionStatusTest extends StatusChangeTest {   
    
    public function testNotificationBodyEvent() {
        $this->assertNotificationBodyField('event', 'customer.subscription.updated');
    } 
    
    public function testNotificationBodyUser() {
        $this->assertNotificationBodyField('user', 'user@example.com');
    }    
    
    public function testNotificationBodyIsStatusChange() {
        $this->assertNotificationBodyField('is_status_change', '1');
    } 

    public function testNotificationBodyPreviousSubscriptionStatus() {
        $this->assertNotificationBodyField('previous_subscription_status', $this->getPreviousSubscriptionStatus());
    }      
    
    public function testNotificationBodyCurrentSubscriptionStatus() {        
        $this->assertNotificationBodyField('subscription_status', $this->getCurrentSubscriptionStatus());
    }     
    
    public function testWebClientSubscriberResponseStatusCode() {        
        $this->assertEquals(
                200,
                $this->getHttpClientService()->getHistoryPlugin()->getLastResponse()->getStatusCode()
        );
    } 
}
