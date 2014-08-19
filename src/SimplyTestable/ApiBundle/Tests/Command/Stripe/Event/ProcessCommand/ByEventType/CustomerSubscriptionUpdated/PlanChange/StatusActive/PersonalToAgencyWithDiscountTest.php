<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionUpdated\PlanChange\StatusActive;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionUpdated\PlanChange\WithDiscountTest;

class PersonalToAgencyWithDiscountTest extends WithDiscountTest {

    protected function getSubscriptionStatus() {
        return 'active';
    }
}
