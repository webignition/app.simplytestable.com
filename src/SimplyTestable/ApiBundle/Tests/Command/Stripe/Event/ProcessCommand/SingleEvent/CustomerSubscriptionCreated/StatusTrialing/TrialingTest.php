<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\CustomerSubscriptionCreated\StatusTrialing;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\CustomerSubscriptionCreated\CustomerSubscriptionCreatedTest;

abstract class TrialingTest extends CustomerSubscriptionCreatedTest {   
    
    protected function getExpectedNotificationBodyFields() {
        return array_merge(parent::getExpectedNotificationBodyFields(), array(
            'trial_start' => '1379776581',
            'trial_end' => '1382368580',
            'trial_period_days' => '30',
            'has_card' => (int)$this->getHasCard()
        ));
    }
    
    protected function getSubscriptionStatus() {
        return 'trialing';
    }     

    protected function getStripeEventFixturePath() {
        return $this->getFixturesDataPath() . '/../../StripeEvents/customer.subscription.created.trialing.json';
    }
}
