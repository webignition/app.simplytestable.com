<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\CustomerSubscriptionDeleted;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\SingleEventTest;

class CustomerSubscriptionDeletedTest extends SingleEventTest {
    
    protected function getHttpFixtureItems() {
        return array(
            "HTTP/1.1 200 OK"
        );
    }
    
    
    public function testNotificationBodyEvent() {        
        $this->assertNotificationBodyField('event', 'customer.subscription.deleted');
    }
    
    public function testNotificationBodyUser() {
        $this->assertNotificationBodyField('user', 'user@example.com');
    }
    
    public function testNotificationBodyPlanName() {
        $this->assertNotificationBodyField('plan_name', 'Personal');
    }    
    
    public function testNotificationBodyActionedBy() {
        $this->assertNotificationBodyField('actioned_by', 'user');
    }   
    
    public function testNotificationBodyIsDuringTrial() {
        $this->assertNotificationBodyField('is_during_trial', 1);
    }       
    
    public function testWebClientSubscriberResponseStatusCode() {        
        $this->assertEquals(
                200,
                $this->getHttpClientService()->getHistoryPlugin()->getLastResponse()->getStatusCode()
        );
    }

    protected function getStripeEventFixturePath() {
        return $this->getFixturesDataPath() . '/../StripeEvents/customer.subscription.deleted-during-trial.json';
    }      
}
