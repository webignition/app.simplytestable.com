<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\CustomerSubscriptionCreated;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\SingleEventTest;

abstract class CustomerSubscriptionCreatedTest extends SingleEventTest {   
    
    protected function getExpectedNotificationBodyFields() {
        return array_merge(parent::getExpectedNotificationBodyFields(), array(
            'status' => $this->getSubscriptionStatus(),
            'plan_name' => 'Basic'            
        ));
    }     
    
    protected function getExpectedNotificationBodyEventName() {
        return 'customer.subscription.created';
    }
    
    abstract protected function getSubscriptionStatus();
    abstract protected function getHasCard();
}
