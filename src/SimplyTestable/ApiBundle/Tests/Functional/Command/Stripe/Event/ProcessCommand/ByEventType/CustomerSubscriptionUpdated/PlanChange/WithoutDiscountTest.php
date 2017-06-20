<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionUpdated\PlanChange;

abstract class WithoutDiscountTest extends PlanChangeTest {

    protected function getExpectedNotificationBodyFields() {
        $fields = array(
            'is_plan_change' => '1',
            'old_plan' => 'Personal',
            'new_plan' => 'Agency',
            'new_amount' => '1900',
            'subscription_status' => $this->getSubscriptionStatus(),
            'currency' => 'gbp'
        );

        if ($this->getSubscriptionStatus() == 'trialing') {
            $fields['trial_end'] = '1405427088';
        }

        return array_merge(parent::getExpectedNotificationBodyFields(), $fields);
    }

    protected function getHasDiscount() {
        return false;
    }

    protected function getStripeEventFixturePaths() {
        return array(
            $this->getFixturesDataPath() . '/../../StripeEvents/WithoutDiscount/customer.subscription.updated.planchange.json'
        );
    }
//
//    protected function getFixtureReplacements() {
//        $fixtureReplacements = parent::getFixtureReplacements();
//        $fixtureReplacements['{{subscription_status}}'] = $this->getSubscriptionStatus();
//
//        return $fixtureReplacements;
//    }

}
