<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionUpdated\PlanChange\StatusActive;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionUpdated\PlanChange\WithoutDiscountTest;

class PersonalToAgencyWithoutDiscountTest extends WithoutDiscountTest {

    protected function getSubscriptionStatus() {
        return 'active';
    }
}
