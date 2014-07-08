<?php

namespace SimplyTestable\ApiBundle\Tests\Services\UserAccountPlanService\Subscribe\Success;

class BasicToBasicTest extends SubscribeTest {

    const PLAN_NAME = 'basic';

    protected function getNewPlanName() {
        return self::PLAN_NAME;
    }

}
