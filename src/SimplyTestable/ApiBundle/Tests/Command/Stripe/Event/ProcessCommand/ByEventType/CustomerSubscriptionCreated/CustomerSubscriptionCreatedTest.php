<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionCreated;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\ByEventType\ByEventTypeTest;

abstract class CustomerSubscriptionCreatedTest extends ByEventTypeTest {   
    
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
