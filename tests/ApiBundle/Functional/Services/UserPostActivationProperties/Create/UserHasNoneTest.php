<?php

namespace Tests\ApiBundle\Functional\Services\UserPostActivationProperties\Create;

use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan;
use Tests\ApiBundle\Factory\UserFactory;

class UserHasNoneTest extends ServiceTest {

    const ACCOUNT_PLAN_NAME = 'personal';
    const COUPON = 'FOO';

    protected function setUp() {
        parent::setUp();

        $accountPlanRepository = $this->container->get('simplytestable.repository.accountplan');

        /* @var Plan $plan */
        $plan = $accountPlanRepository->findOneBy([
            'name' => self::ACCOUNT_PLAN_NAME,
        ]);

        $userFactory = new UserFactory($this->container);

        $this->userPostActivationProperties = $this->getUserPostActivationPropertiesService()->create(
            $userFactory->create(),
            $plan,
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
