<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventSequence\CustomerSubscriptionDeleted;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventSequence\EventSequenceTest;

class CustomerSubscriptionDeletedTest extends EventSequenceTest {    
    
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

    protected function getStripeEventFixturePath() {
        return $this->getFixturesDataPath() . '/../StripeEvents/customer.subscription.deleted-during-trial.json';
    }      
}
