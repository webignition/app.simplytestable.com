<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\UserCreation\ActivateAction\Success;

class PremiumPlanTest extends SuccessTest {

    const USER_EMAIL = 'user@example.com';
    const USER_PASSWORD = 'password';
    const USER_PLAN = 'personal';


    public function setUp() {
        parent::setUp();

        $this->getUserCreationController('createAction', array(
            'email' => self::USER_EMAIL,
            'password' => self::USER_PASSWORD,
            'plan' => self::USER_PLAN
        ))->createAction();

        $this->user = $this->getUserService()->findUserByEmail(self::USER_EMAIL);

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

