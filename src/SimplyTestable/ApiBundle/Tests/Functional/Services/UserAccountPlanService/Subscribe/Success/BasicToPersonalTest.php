<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\UserAccountPlanService\Subscribe\Success;

class BasicToPersonalTest extends SubscribeTest {

    const PLAN_NAME = 'personal';

    protected function getNewPlanName() {
        return self::PLAN_NAME;
    }

}
