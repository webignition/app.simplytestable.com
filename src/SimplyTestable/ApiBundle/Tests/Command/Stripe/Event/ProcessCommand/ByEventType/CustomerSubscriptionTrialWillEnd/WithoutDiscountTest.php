<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionTrialWillEnd;

abstract class WithoutDiscountTest extends CustomerSubscriptionTrialWillEndTest {

    protected function getExpectedNotificationBodyFields() {
        return array_merge(parent::getExpectedNotificationBodyFields(), array(
            'has_card' => (int)$this->getHasCard(),
            'plan_name' => 'Agency',
            'trial_end' => '1382368580',
            'plan_amount' => '1900',
            'plan_currency' => 'gbp'
        ));
    }

    
    protected function getStripeEventFixturePaths() {
        return array(
            $this->getFixturesDataPath() . '/../StripeEvents/customer.subscription.trial_will_end.json'
        );
    }     
}
