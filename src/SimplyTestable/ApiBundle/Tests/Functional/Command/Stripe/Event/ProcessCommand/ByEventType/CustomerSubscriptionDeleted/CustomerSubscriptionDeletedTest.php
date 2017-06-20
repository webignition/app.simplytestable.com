<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionDeleted;

use SimplyTestable\ApiBundle\Tests\Functional\Command\Stripe\Event\ProcessCommand\ByEventType\ByEventTypeTest;

abstract class CustomerSubscriptionDeletedTest extends ByEventTypeTest {

    protected function getExpectedNotificationBodyEventName() {
        return 'customer.subscription.deleted';
    }
}
