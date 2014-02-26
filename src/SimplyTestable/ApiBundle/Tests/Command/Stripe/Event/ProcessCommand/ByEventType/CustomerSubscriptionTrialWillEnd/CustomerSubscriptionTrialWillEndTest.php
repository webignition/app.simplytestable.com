<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionTrialWillEnd;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\ByEventType\ByEventTypeTest;

abstract class CustomerSubscriptionTrialWillEndTest extends ByEventTypeTest {   
    
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
    
    protected function getStripeEventFixturePaths() {
        return array(
            $this->getFixturesDataPath() . '/../StripeEvents/customer.subscription.trial_will_end.json'
        );
    }     
}
