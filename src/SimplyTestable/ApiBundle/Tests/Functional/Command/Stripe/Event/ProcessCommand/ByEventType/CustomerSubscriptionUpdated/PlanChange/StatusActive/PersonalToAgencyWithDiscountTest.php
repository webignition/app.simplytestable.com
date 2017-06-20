<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionUpdated\PlanChange\StatusActive;

use SimplyTestable\ApiBundle\Tests\Functional\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionUpdated\PlanChange\WithDiscountTest;

class PersonalToAgencyWithDiscountTest extends WithDiscountTest {

    protected function getSubscriptionStatus() {
        return 'active';
    }
}
