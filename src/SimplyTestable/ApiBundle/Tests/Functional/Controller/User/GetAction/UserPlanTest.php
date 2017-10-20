<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\User\GetAction;

use SimplyTestable\ApiBundle\Controller\UserController;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class UserPlanTest extends BaseSimplyTestableTestCase
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

    public function testHasUserPlan()
    {
        $user = $this->userFactory->create();
        $this->setUser($user);

        $responseObject = json_decode($this->userController->getAction()->getContent());

        $this->assertTrue(isset($responseObject->user_plan));
        $this->assertTrue(isset($responseObject->user_plan->plan));
    }

    public function testForUserWithBasicPlan()
    {
        $user = $this->userFactory->create();
        $this->setUser($user);

        $responseObject = json_decode($this->userController->getAction()->getContent());

        $this->assertEquals('basic', $responseObject->user_plan->plan->name);
        $this->assertFalse($responseObject->user_plan->plan->is_premium);
    }

    public function testForUserWithPremiumPlan()
    {
        $this->markTestSkipped('Re-implement in 1299');

        $user = $this->userFactory->create();
        $this->setUser($user);

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));

        $responseObject = json_decode($this->userController->getAction()->getContent());

        $this->assertEquals('personal', $responseObject->user_plan->plan->name);
        $this->assertTrue($responseObject->user_plan->plan->is_premium);
        $this->assertEquals(self::DEFAULT_TRIAL_PERIOD, $responseObject->user_plan->start_trial_period);
    }

    public function testRetrieveForUserWhereIsActiveIsZero()
    {
        $this->markTestSkipped('Re-implement in 1299');

        $user = $this->userFactory->create();
        $this->setUser($user);

        $this->getUserAccountPlanService()->deactivateAllForUser($user);

        $responseObject = json_decode($this->userController->getAction()->getContent());

        $this->assertEquals('basic', $responseObject->user_plan->plan->name);
    }

    public function testRetrieveForUserWhereIsActiveIsZeroAndUserHasMany()
    {
        $this->markTestSkipped('Re-implement in 1299');

        $user = $this->userFactory->create();
        $this->setUser($user);

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));
        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('agency'));

        $this->getUserAccountPlanService()->deactivateAllForUser($user);

        $responseObject = json_decode($this->userController->getAction()->getContent());

        $this->assertEquals('agency', $responseObject->user_plan->plan->name);
    }


    public function testStartTrialPeriodForUserWithBasicPlan()
    {
        $this->markTestSkipped('Re-implement in 1299');

        $user = $this->userFactory->create();
        $this->setUser($user);

        $responseObject = json_decode($this->userController->getAction()->getContent());
        $this->assertEquals(self::DEFAULT_TRIAL_PERIOD, $responseObject->user_plan->start_trial_period);
    }

    public function testStartTrialPeriodForUserWithPartExpiredPremiumTrial()
    {
        $this->markTestSkipped('Re-implement in 1299');

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

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('agency'));

        $responseObject = json_decode($this->userController->getAction()->getContent());
        $this->assertEquals($trialDaysRemaining, $responseObject->user_plan->start_trial_period);
    }


    public function testStartTrialPeriodForUserWithPartExpiredPremiumTrialBackOnBasic()
    {
        $this->markTestSkipped('Re-implement in 1299');

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

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('basic'));

        $responseObject = json_decode($this->userController->getAction()->getContent());
        $this->assertEquals($trialDaysRemaining, $responseObject->user_plan->start_trial_period);
    }


    public function testGetTeamPlanForTeamMember()
    {
        $this->markTestSkipped('Re-implement in 1299');

        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $user = $this->userFactory->createAndActivateUser();
        $this->setUser($user);

        $this->getTeamMemberService()->add($team, $user);

        $this->getUserAccountPlanService()->subscribe($leader, $this->getAccountPlanService()->find('agency'));

        $responseObject = json_decode($this->userController->getAction()->getContent());

        $this->assertEquals('agency', $responseObject->user_plan->plan->name);
    }
}


