<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionTrialWillEnd;

use SimplyTestable\ApiBundle\Tests\Functional\Command\Stripe\Event\ProcessCommand\ByEventType\ByEventTypeTest;

abstract class CustomerSubscriptionTrialWillEndTest extends ByEventTypeTest {

    abstract protected function getHasCard();

    protected function getExpectedNotificationBodyEventName() {
        return 'customer.subscription.trial_will_end';
    }
}
