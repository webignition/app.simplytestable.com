<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\User\GetAction;

use SimplyTestable\ApiBundle\Controller\UserController;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;

class GetActionStripeCustomerTest extends BaseControllerJsonTestCase
{
    const DEFAULT_TRIAL_PERIOD = 30;

    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * @var UserController
     */
    private $userController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userFactory = new UserFactory($this->container);
        $this->userController = new UserController();
        $this->userController->setContainer($this->container);
    }

    public function testForUserWithBasicPlan() {
        $user = $this->userFactory->create();
        $this->setUser($user);

        $responseObject = json_decode($this->userController->getAction()->getContent());
        $this->assertFalse(isset($responseObject->stripe_customer));
    }

    public function testForUserWithPremiumPlan() {
        $user = $this->userFactory->create();
        $this->setUser($user);

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));

        $responseObject = json_decode($this->userController->getAction()->getContent());
        $this->assertTrue(isset($responseObject->stripe_customer));
    }

    public function testForUserWhereIsActiveIsZero() {
        $user = $this->userFactory->create();
        $this->setUser($user);

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));

        $this->getUserAccountPlanService()->deactivateAllForUser($user);

        $responseObject = json_decode($this->userController->getAction()->getContent());
        $this->assertTrue(isset($responseObject->stripe_customer));
    }

    public function testForUserWhereIsActiveIsZeroAndUserHasMany() {
        $user = $this->userFactory->create();
        $this->setUser($user);

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));
        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('agency'));

        $this->getUserAccountPlanService()->deactivateAllForUser($user);

        $responseObject = json_decode($this->userController->getAction()->getContent());
        $this->assertTrue(isset($responseObject->stripe_customer));
    }


    public function testForUserWithPreviousPremiumTrialBackOnBasic() {
        $trialDaysRemaining = rand(1, self::DEFAULT_TRIAL_PERIOD);

        $user = $this->userFactory->create();
        $this->setUser($user);

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));

        // Mock the fact that the Stripe customer.subscription.trial_end is
        // $trialDaysPassed days from now
        $this->getStripeService()->addResponseData('getCustomer', array(
            'subscription' => array(
                'trial_end' => time() + (86400 * $trialDaysRemaining)
            )
        ));

        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction($user->getEmail(), 'basic');

        $responseObject = json_decode($this->userController->getAction()->getContent());
        $this->assertTrue(isset($responseObject->stripe_customer));
    }


    public function testTeamMemberSummaryExcludesTeamStripeCustomer() {
        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $this->setUser($leader);

        $this->getUserAccountPlanService()->subscribe($leader, $this->getAccountPlanService()->find('personal'));

        $member = $this->userFactory->createAndActivateUser();

        $team = $this->getTeamService()->create('Foo', $leader);

        $this->getTeamMemberService()->add($team, $member);

        $this->setUser($member);

        $responseObject = json_decode($this->userController->getAction()->getContent());
        $this->assertFalse(isset($responseObject->stripe_customer));

    }
}


