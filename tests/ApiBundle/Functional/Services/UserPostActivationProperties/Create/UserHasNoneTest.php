<?php

namespace Tests\ApiBundle\Functional\Services\UserPostActivationProperties\Create;

use Tests\ApiBundle\Factory\UserFactory;

class UserHasNoneTest extends ServiceTest {

    const ACCOUNT_PLAN_NAME = 'personal';
    const COUPON = 'FOO';

    protected function setUp() {
        parent::setUp();

        $accountPlanService = $this->container->get('simplytestable.services.accountplanservice');

        $userFactory = new UserFactory($this->container);

        $this->userPostActivationProperties = $this->getUserPostActivationPropertiesService()->create(
            $userFactory->create(),
            $accountPlanService->find(self::ACCOUNT_PLAN_NAME),
            self::COUPON
        );
    }


    public function testUserPostActivationPropertiesIsOfCorrectType() {
        $this->assertInstanceOf('SimplyTestable\ApiBundle\Entity\UserPostActivationProperties', $this->userPostActivationProperties);
    }


    public function testAccountPlanIsSet() {
        $this->assertEquals(self::ACCOUNT_PLAN_NAME, $this->userPostActivationProperties->getAccountPlan()->getName());
    }


    public function testCouponIsSet() {
        $this->assertEquals(self::COUPON, $this->userPostActivationProperties->getCoupon());
    }

}
