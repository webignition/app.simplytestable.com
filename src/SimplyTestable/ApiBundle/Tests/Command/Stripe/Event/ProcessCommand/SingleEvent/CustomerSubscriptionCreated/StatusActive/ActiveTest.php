<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\CustomerSubscriptionCreated\StatusActive;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\CustomerSubscriptionCreated\CustomerSubscriptionCreatedTest;

class ActiveTest extends CustomerSubscriptionCreatedTest {
    
    protected function getExpectedNotificationBodyFields() {
        return array_merge(parent::getExpectedNotificationBodyFields(), array(
            'current_period_start' => '1392757613',
            'current_period_end' => '1395176813',
            'amount' => '900'
        ));
    }  
    
    protected function getSubscriptionStatus() {
        return 'active';
    }

    protected function getStripeEventFixturePath() {
        return $this->getFixturesDataPath() . '/../../StripeEvents/customer.subscription.created.active.json';
    }
    
    protected function getStripeServiceResponseMethod() {
        return 'getCustomer';
    }
    
    protected function getStripeServiceResponseData() {
        return array(
            'active_card' => array(
                'exp_month' => '01',
                'exp_year' => '99',
                'last4' => '1234',
                'type' => 'Foo'
            )
        );
    }

    protected function getHasCard() {
        return true;
    }   
}
