<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\UserAccountPlanService\Subscribe\Success;

class BasicToBasicTest extends SubscribeTest {

    const PLAN_NAME = 'basic';

    protected function getNewPlanName() {
        return self::PLAN_NAME;
    }

}
