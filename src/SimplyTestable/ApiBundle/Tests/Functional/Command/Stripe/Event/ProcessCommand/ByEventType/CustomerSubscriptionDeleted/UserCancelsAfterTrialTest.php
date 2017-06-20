<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionDeleted;

class UserCancelsAfterTrialTest extends CustomerSubscriptionDeletedTest {

    protected function getExpectedNotificationBodyFields() {
        return array_merge(parent::getExpectedNotificationBodyFields(), array(
            'plan_name' => 'Agency',
            'actioned_by' => 'user',
            'is_during_trial' => '0',
        ));
    }

    protected function getStripeEventFixturePaths() {
        return array(
            $this->getFixturesDataPath() . '/StripeEvents/customer.subscription.deleted.json'
        );
    }
}
