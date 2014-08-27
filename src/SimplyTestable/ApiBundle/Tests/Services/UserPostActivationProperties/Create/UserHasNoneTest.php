<?php

namespace SimplyTestable\ApiBundle\Tests\Services\UserPostActivationProperties\Create;

class UserHasNoneTest extends ServiceTest {

    const ACCOUNT_PLAN_NAME = 'personal';
    const COUPON = 'FOO';

    public function setUp() {
        parent::setUp();

        $this->userPostActivationProperties = $this->getUserPostActivationPropertiesService()->create(
            $this->createAndFindUser('user@example.com'),
            $this->getAccountPlanService()->find(self::ACCOUNT_PLAN_NAME),
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
