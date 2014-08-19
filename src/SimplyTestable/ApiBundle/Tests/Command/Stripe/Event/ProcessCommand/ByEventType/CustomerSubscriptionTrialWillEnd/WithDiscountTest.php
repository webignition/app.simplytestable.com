<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionTrialWillEnd;

abstract class WithDiscountTest extends CustomerSubscriptionTrialWillEndTest {

    protected function getExpectedNotificationBodyFields() {
        return array_merge(parent::getExpectedNotificationBodyFields(), array(
            'has_card' => (int)$this->getHasCard(),
            'plan_name' => 'Agency',
            'trial_end' => '1382368580',
            'plan_amount' => '1520',
        ));
    }

    
    protected function getStripeEventFixturePaths() {
        return [
            $this->getFixturesDataPath() . '/../StripeEvents/customer.created.json',
            $this->getFixturesDataPath() . '/../StripeEvents/customer.updated.json',
            $this->getFixturesDataPath() . '/../StripeEvents/customer.subscription.trial_will_end.json'
        ];
    }     
}
