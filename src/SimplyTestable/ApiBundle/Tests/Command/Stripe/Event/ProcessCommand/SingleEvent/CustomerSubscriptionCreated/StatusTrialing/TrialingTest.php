<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\CustomerSubscriptionCreated\StatusTrialing;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\CustomerSubscriptionCreated\CustomerSubscriptionCreatedTest;

abstract class TrialingTest extends CustomerSubscriptionCreatedTest {   

    public function testNotificationBodyTrialStart() {
        $this->assertNotificationBodyField('trial_start', '1379776581');
    }
    
    public function testNotificationBodyTrialEnd() {
        $this->assertNotificationBodyField('trial_end', '1382368580');
    }
    
    public function testNotificationBodyTrialPeriodDays() {
        $this->assertNotificationBodyField('trial_period_days', '30');
    }
    
    public function testNotificationBodyHasCard() {
        $this->assertNotificationBodyField('has_card', (int)$this->getHasCard());
    }    
    
    protected function getSubscriptionStatus() {
        return 'trialing';
    }     

    protected function getStripeEventFixturePath() {
        return $this->getFixturesDataPath() . '/../../StripeEvents/customer.subscription.created.trialing.json';
    }
}
