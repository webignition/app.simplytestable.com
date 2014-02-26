<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionDeleted;

class UserCancelsDuringTrialTest extends CustomerSubscriptionDeletedTest {    
    
    protected function getExpectedNotificationBodyFields() {
        return array_merge(parent::getExpectedNotificationBodyFields(), array(
            'plan_name' => 'Personal',
            'actioned_by' => 'user',
            'is_during_trial' => '1',
        ));
    }
    
    protected function getStripeEventFixturePaths() {
        return array(
            $this->getFixturesDataPath() . '/StripeEvents/customer.subscription.deleted.json'
        );
    }     
}
