<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\CustomerSubscriptionTrialWillEnd;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\SingleEventTest;

abstract class CustomerSubscriptionTrialWillEndTest extends SingleEventTest {   
    
    abstract protected function getHasCard();
    
    public function testNotificationBodyEvent() {        
        $this->assertNotificationBodyField('event', 'customer.subscription.trial_will_end');
    }
    
    public function testNotificationBodyUser() {
        $this->assertNotificationBodyField('user', 'user@example.com');
    }
    
    public function testNotificationBodyHasCard() {
        $this->assertNotificationBodyField('has_card', (int)$this->getHasCard());
    }
    
    public function testNotificationBodyPlanName() {
        $this->assertNotificationBodyField('plan_name', 'Agency');
    }     
    
    public function testNotificationBodyTrialEnd() {
        $this->assertNotificationBodyField('trial_end', '1382368580');
    }     
    
    public function testNotificationBodyPlanAmount() {
        $this->assertNotificationBodyField('plan_amount', '1900');
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
        return $this->getFixturesDataPath() . '/../StripeEvents/customer.subscription.trial_will_end.json';
    }
}
