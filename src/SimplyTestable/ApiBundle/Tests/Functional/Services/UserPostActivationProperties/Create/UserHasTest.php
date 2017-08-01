<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\UserPostActivationProperties\Create;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class UserHasTest extends ServiceTest {

    const ACCOUNT_PLAN_NAME = 'agency';
    const COUPON = 'FOO';

    protected function setUp() {
        parent::setUp();

        $userFactory = new UserFactory($this->container);

        $user = $userFactory->create();
        $this->userPostActivationProperties = $this->getUserPostActivationPropertiesService()->create($user, $this->getAccountPlanService()->find('personal'));

        $this->userPostActivationProperties = $this->getUserPostActivationPropertiesService()->create(
            $user,
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
