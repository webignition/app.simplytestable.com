<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionUpdated\PlanChange;

use SimplyTestable\ApiBundle\Tests\Functional\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionUpdated\CustomerSubscriptionUpdatedTest;

abstract class PlanChangeTest extends CustomerSubscriptionUpdatedTest {

    abstract protected function getSubscriptionStatus();
    abstract protected function getHasDiscount();

    protected function getExpectedNotificationBodyFields() {
        $fields = array(
            'is_plan_change' => '1',
            'old_plan' => 'Personal',
            'new_plan' => 'Agency',
            'new_amount' => '1900',
            'subscription_status' => $this->getSubscriptionStatus()
        );

        if ($this->getSubscriptionStatus() == 'trialing') {
            $fields['trial_end'] = '1405427088';
        }

        return array_merge(parent::getExpectedNotificationBodyFields(), $fields);
    }

//    protected function getStripeEventFixturePaths() {
//        return array(
//            $this->getFixturesDataPath() . '/../../StripeEvents/customer.subscription.updated.planchange.json'
//        );
//    }

    protected function getFixtureReplacements() {
        $fixtureReplacements = parent::getFixtureReplacements();
        $fixtureReplacements['{{subscription_status}}'] = $this->getSubscriptionStatus();

        return $fixtureReplacements;
    }

}
