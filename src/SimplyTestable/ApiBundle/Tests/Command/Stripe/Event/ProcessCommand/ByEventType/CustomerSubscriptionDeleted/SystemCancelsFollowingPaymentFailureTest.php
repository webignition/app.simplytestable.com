<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionDeleted;

class SystemCancelsFollowingPaymentFailureTest extends CustomerSubscriptionDeletedTest {    
    
    protected function getExpectedNotificationBodyFields() {
        return array_merge(parent::getExpectedNotificationBodyFields(), array(
            'plan_name' => 'Agency',
            'actioned_by' => 'system'
        ));
    }
    
    protected function getStripeEventFixturePaths() {
        return array(
            $this->getFixturesDataPath() . '/StripeEvents/invoice.payment_failed.json',
            $this->getFixturesDataPath() . '/StripeEvents/customer.subscription.deleted.json'
        );
    }     
}
