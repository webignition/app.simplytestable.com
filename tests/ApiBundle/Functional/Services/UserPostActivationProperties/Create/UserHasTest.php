<?php

namespace Tests\ApiBundle\Functional\Services\UserPostActivationProperties\Create;

use Tests\ApiBundle\Factory\UserFactory;

class UserHasTest extends ServiceTest {

    const ACCOUNT_PLAN_NAME = 'agency';
    const COUPON = 'FOO';

    protected function setUp() {
        parent::setUp();

        $accountPlanService = $this->container->get('simplytestable.services.accountplan');

        $personalPlan = $accountPlanService->get('personal');
        $agencyPlan = $accountPlanService->get(self::ACCOUNT_PLAN_NAME);

        $userFactory = new UserFactory($this->container);

        $user = $userFactory->create();
        $this->userPostActivationProperties = $this->getUserPostActivationPropertiesService()->create(
            $user,
            $personalPlan
        );

        $this->userPostActivationProperties = $this->getUserPostActivationPropertiesService()->create(
            $user,
            $agencyPlan,
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
