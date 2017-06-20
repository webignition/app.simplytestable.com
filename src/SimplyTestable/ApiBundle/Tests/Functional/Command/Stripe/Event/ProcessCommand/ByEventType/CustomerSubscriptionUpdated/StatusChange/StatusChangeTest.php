<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionUpdated\StatusChange;

use SimplyTestable\ApiBundle\Tests\Functional\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionUpdated\CustomerSubscriptionUpdatedTest;

abstract class StatusChangeTest extends CustomerSubscriptionUpdatedTest {

    abstract protected function getCurrentSubscriptionStatus();
    abstract protected function getPreviousSubscriptionStatus();

    protected function getExpectedNotificationBodyFields() {
        return array_merge(parent::getExpectedNotificationBodyFields(), array(
            'is_status_change' => '1',
            'previous_subscription_status' => $this->getPreviousSubscriptionStatus(),
            'subscription_status' => $this->getCurrentSubscriptionStatus(),
            'plan_name' => 'Agency',
            'plan_amount' => '1900',
        ));
    }

    protected function getStripeEventFixturePaths() {
        return array(
            $this->getFixturesDataPath() . '/../StripeEvents/customer.subscription.updated.statuschange.json'
        );
    }

    protected function getFixtureReplacements() {
        $fixtureReplacements = parent::getFixtureReplacements();
        $fixtureReplacements['{{subscription_status}}'] = $this->getCurrentSubscriptionStatus();
        $fixtureReplacements['{{previous_subscription_status}}'] = $this->getPreviousSubscriptionStatus();

        return $fixtureReplacements;
    }
}
