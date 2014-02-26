<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\CustomerSubscriptionDeleted;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\SingleEventTest;

class CustomerSubscriptionDeletedTest extends SingleEventTest {    
    
    protected function getExpectedNotificationBodyFields() {
        return array_merge(parent::getExpectedNotificationBodyFields(), array(
            'plan_name' => 'Personal',
            'actioned_by' => 'user',
            'is_during_trial' => '1',
        ));
    }     
    
    protected function getExpectedNotificationBodyEventName() {
        return 'customer.subscription.deleted';
    }

    protected function getStripeEventFixturePath() {
        return $this->getFixturesDataPath() . '/../StripeEvents/customer.subscription.deleted-during-trial.json';
    }      
}
