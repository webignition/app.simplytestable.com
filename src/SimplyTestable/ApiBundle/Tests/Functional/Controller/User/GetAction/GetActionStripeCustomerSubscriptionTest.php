<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\User\GetAction;

use SimplyTestable\ApiBundle\Controller\UserController;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;

class GetActionStripeCustomerSubscriptionTest extends BaseControllerJsonTestCase
{
    const DEFAULT_TRIAL_PERIOD = 30;

    public function testForUserWithPremiumPlan()
    {
        $userFactory = new UserFactory($this->container);

        $user = $userFactory->create();
        $this->setUser($user);

        $userAccountPlan = $this->getUserAccountPlanService()->subscribe(
            $user,
            $this->getAccountPlanService()->find('personal')
        );

        $userController = new UserController();
        $userController->setContainer($this->container);

        $responseObject = json_decode($userController->getAction()->getContent());
        $this->assertTrue(isset($responseObject->stripe_customer->subscription));
        $this->assertTrue(isset($responseObject->stripe_customer->subscription->plan));

        $this->assertEquals('trialing', $responseObject->stripe_customer->subscription->status);
        $this->assertEquals(
            $userAccountPlan->getStripeCustomer(),
            $responseObject->stripe_customer->subscription->customer
        );
    }
}
