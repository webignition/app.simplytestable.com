<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\CustomerSubscriptionCreated\StatusActive;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\CustomerSubscriptionCreated\CustomerSubscriptionCreatedTest;

abstract class ActiveTest extends CustomerSubscriptionCreatedTest {

    public function testNotificationBodyCurrentPeriodStart() {
        $this->assertNotificationBodyField('current_period_start', '1392757613');
    }
    
    public function testNotificationBodyCurrentPeriodEnd() {
        $this->assertNotificationBodyField('current_period_end', '1395176813');
    }
    
    public function testNotificationBodyAmount() {
        $this->assertNotificationBodyField('amount', '900');
    }  
    
    protected function getSubscriptionStatus() {
        return 'active';
    }

    protected function getStripeEventFixturePath() {
        return $this->getFixturesDataPath() . '/../../StripeEvents/customer.subscription.created.active.json';
    }
}
