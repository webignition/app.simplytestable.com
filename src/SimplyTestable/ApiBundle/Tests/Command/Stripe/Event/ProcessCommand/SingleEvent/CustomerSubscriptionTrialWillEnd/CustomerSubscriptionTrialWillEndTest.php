<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\CustomerSubscriptionTrialWillEnd;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\SingleEventTest;

abstract class CustomerSubscriptionTrialWillEndTest extends SingleEventTest {   
    
    abstract protected function getHasCard();
    
    protected function getExpectedNotificationBodyFields() {
        return array_merge(parent::getExpectedNotificationBodyFields(), array(
            'has_card' => (int)$this->getHasCard(),
            'plan_name' => 'Agency',
            'trial_end' => '1382368580',
            'plan_amount' => '1900',
        ));
    }     
    
    protected function getExpectedNotificationBodyEventName() {
        return 'customer.subscription.trial_will_end';
    } 

    protected function getStripeEventFixturePath() {
        return $this->getFixturesDataPath() . '/../StripeEvents/customer.subscription.trial_will_end.json';
    }
}
