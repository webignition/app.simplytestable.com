<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\CustomerSubscriptionUpdated\PlanChange\StatusActive;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\CustomerSubscriptionUpdated\PlanChange\PlanChangeTest;

class PersonalToAgencyTest extends PlanChangeTest { 

    protected function getSubscriptionStatus() {
        return 'active';
    }
}
