<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\UserCreation\ActivateAction\Success;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class PremiumPlanTest extends SuccessTest {

    const USER_EMAIL = 'user@example.com';
    const USER_PASSWORD = 'password';
    const USER_PLAN = 'personal';


    protected function setUp() {
        parent::setUp();

        $userFactory = new UserFactory($this->container);
        $this->user = $userFactory->create([
            UserFactory::KEY_EMAIL => self::USER_EMAIL,
            UserFactory::KEY_PLAN_NAME => self::USER_PLAN,
        ]);

        $methodName = $this->getActionNameFromRouter();
        $this->response = $this->getCurrentController()->$methodName($this->user->getConfirmationToken());
    }

    public function testUserIsOnSelectedPlan() {
        $this->assertEquals(self::USER_PLAN, $this->getUserAccountPlanService()->getForUser($this->user)->getPlan()->getName());
    }

    public function testUserHasStripeCustomer() {
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($this->user);
        $this->assertNotNull($userAccountPlan->getStripeCustomer());
    }


    public function testUserNoLongerHasPostActivationProperties() {
        $this->assertFalse($this->getUserPostActivationPropertiesService()->hasForUser($this->user));
    }


}

