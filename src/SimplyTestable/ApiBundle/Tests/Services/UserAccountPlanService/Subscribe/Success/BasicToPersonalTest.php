<?php

namespace SimplyTestable\ApiBundle\Tests\Services\UserAccountPlanService\Subscribe\Success;

class BasicToPersonalTest extends SubscribeTest {

    const PLAN_NAME = 'personal';

    protected function getNewPlanName() {
        return self::PLAN_NAME;
    }

}
