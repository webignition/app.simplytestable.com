<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionUpdated;

use SimplyTestable\ApiBundle\Tests\Functional\Command\Stripe\Event\ProcessCommand\ByEventType\ByEventTypeTest;

abstract class CustomerSubscriptionUpdatedTest extends ByEventTypeTest {

    protected function getExpectedNotificationBodyEventName() {
        return 'customer.subscription.updated';
    }
}
