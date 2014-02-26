<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\CustomerSubscriptionUpdated;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\SingleEventTest;

abstract class CustomerSubscriptionUpdatedTest extends SingleEventTest {     
    
    protected function getExpectedNotificationBodyEventName() {
        return 'customer.subscription.updated';
    }
}
