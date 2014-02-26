<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\CustomerSubscriptionUpdated\PlanChange\StatusActive;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\CustomerSubscriptionUpdated\PlanChange\PlanChangeTest;

class PersonalToAgencyTest extends PlanChangeTest { 

    protected function getSubscriptionStatus() {
        return 'active';
    }
}
