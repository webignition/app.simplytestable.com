<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\User\GetAction;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;

class UserPlanTest extends BaseControllerJsonTestCase
{
    const DEFAULT_TRIAL_PERIOD = 30;

    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userFactory = new UserFactory($this->container);
    }

    public function testHasUserPlan()
    {
        $user = $this->userFactory->create();
        $this->getUserService()->setUser($user);

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());

        $this->assertTrue(isset($responseObject->user_plan));
        $this->assertTrue(isset($responseObject->user_plan->plan));
    }

    public function testForUserWithBasicPlan()
    {
        $user = $this->userFactory->create();
        $this->getUserService()->setUser($user);

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());

        $this->assertEquals('basic', $responseObject->user_plan->plan->name);
        $this->assertFalse($responseObject->user_plan->plan->is_premium);
    }

    public function testForUserWithPremiumPlan()
    {
        $user = $this->userFactory->create();
        $this->getUserService()->setUser($user);

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());

        $this->assertEquals('personal', $responseObject->user_plan->plan->name);
        $this->assertTrue($responseObject->user_plan->plan->is_premium);
        $this->assertEquals(self::DEFAULT_TRIAL_PERIOD, $responseObject->user_plan->start_trial_period);
    }

    public function testRetrieveForUserWhereIsActiveIsZero()
    {
        $user = $this->userFactory->create();
        $this->getUserService()->setUser($user);

        $this->getUserAccountPlanService()->deactivateAllForUser($user);

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());

        $this->assertEquals('basic', $responseObject->user_plan->plan->name);
    }

    public function testRetrieveForUserWhereIsActiveIsZeroAndUserHasMany()
    {
        $user = $this->userFactory->create();
        $this->getUserService()->setUser($user);

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));
        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('agency'));

        $this->getUserAccountPlanService()->deactivateAllForUser($user);

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());

        $this->assertEquals('agency', $responseObject->user_plan->plan->name);
    }


    public function testStartTrialPeriodForUserWithBasicPlan()
    {
        $user = $this->userFactory->create();
        $this->getUserService()->setUser($user);

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());
        $this->assertEquals(self::DEFAULT_TRIAL_PERIOD, $responseObject->user_plan->start_trial_period);
    }

    public function testStartTrialPeriodForUserWithPartExpiredPremiumTrial()
    {
        $trialDaysRemaining = rand(1, self::DEFAULT_TRIAL_PERIOD);
        $user = $this->userFactory->create();
        $this->getUserService()->setUser($user);

        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction(
            $user->getEmail(),
            'personal'
        );

        // Mock the fact that the Stripe customer.subscription.trial_end is
        // $trialDaysPassed days from now
        $this->getStripeService()->addResponseData('getCustomer', array(
            'subscription' => array(
                'trial_end' => time() + (86400 * $trialDaysRemaining)
            )
        ));

        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction(
            $user->getEmail(),
            'agency'
        );

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());
        $this->assertEquals($trialDaysRemaining, $responseObject->user_plan->start_trial_period);
    }


    public function testStartTrialPeriodForUserWithPartExpiredPremiumTrialBackOnBasic()
    {
        $trialDaysRemaining = rand(1, self::DEFAULT_TRIAL_PERIOD);

        $user = $this->userFactory->create();
        $this->getUserService()->setUser($user);

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));

        // Mock the fact that the Stripe customer.subscription.trial_end is
        // $trialDaysPassed days from now
        $this->getStripeService()->addResponseData('getCustomer', array(
            'subscription' => array(
                'trial_end' => time() + (86400 * $trialDaysRemaining)
            )
        ));

        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction(
            $user->getEmail(),
            'basic'
        );

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());
        $this->assertEquals($trialDaysRemaining, $responseObject->user_plan->start_trial_period);
    }


    public function testGetTeamPlanForTeamMember()
    {
        $leader = $this->userFactory->createAndActivateUser('leader@example.com');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $user = $this->userFactory->createAndActivateUser();
        $this->getUserService()->setUser($user);

        $this->getTeamMemberService()->add($team, $user);

        $this->getUserAccountPlanService()->subscribe($leader, $this->getAccountPlanService()->find('agency'));

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());

        $this->assertEquals('agency', $responseObject->user_plan->plan->name);
    }
}


