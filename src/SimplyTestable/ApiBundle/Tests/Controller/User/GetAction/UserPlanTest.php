<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\User\GetAction;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class UserPlanTest extends BaseControllerJsonTestCase {
    
    const DEFAULT_TRIAL_PERIOD = 30;

    public function testHasUserPlan() {
        $email = 'user1@example.com';
        $password = 'password1';

        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());

        $this->assertTrue(isset($responseObject->user_plan));
        $this->assertTrue(isset($responseObject->user_plan->plan));
    }

    public function testForUserWithBasicPlan() {
        $email = 'user1@example.com';
        $password = 'password1';

        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());

        $this->assertEquals('basic', $responseObject->user_plan->plan->name);
        $this->assertFalse($responseObject->user_plan->plan->is_premium);
    }

    public function testForUserWithPremiumPlan() {
        $email = 'user1@example.com';
        $password = 'password1';

        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());

        $this->assertEquals('personal', $responseObject->user_plan->plan->name);
        $this->assertTrue($responseObject->user_plan->plan->is_premium);
        $this->assertEquals(self::DEFAULT_TRIAL_PERIOD, $responseObject->user_plan->start_trial_period);
    }

    public function testRetrieveForUserWhereIsActiveIsZero() {
        $email = 'user1@example.com';
        $password = 'password1';

        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);

        $this->getUserAccountPlanService()->deactivateAllForUser($user);

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());

        $this->assertEquals('basic', $responseObject->user_plan->plan->name);
    }

    public function testRetrieveForUserWhereIsActiveIsZeroAndUserHasMany() {
        $email = 'user1@example.com';
        $password = 'password1';

        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));
        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('agency'));

        $this->getUserAccountPlanService()->deactivateAllForUser($user);

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());

        $this->assertEquals('agency', $responseObject->user_plan->plan->name);  ;
    }


    public function testStartTrialPeriodForUserWithBasicPlan() {
        $email = 'user1@example.com';
        $password = 'password1';

        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());
        $this->assertEquals(self::DEFAULT_TRIAL_PERIOD, $responseObject->user_plan->start_trial_period);
    }

    public function testStartTrialPeriodForUserWithPartExpiredPremiumTrial() {
        $trialDaysRemaining = rand(1, self::DEFAULT_TRIAL_PERIOD);
        $email = 'user-test-retention-of-trial-period@example.com';
        $password = 'password1';

        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);

        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction($email, 'personal');

        // Mock the fact that the Stripe customer.subscription.trial_end is
        // $trialDaysPassed days from now
        $this->getStripeService()->addResponseData('getCustomer', array(
            'subscription' => array(
                'trial_end' => time() + (86400 * $trialDaysRemaining)
            )
        ));

        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction($email, 'agency');

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());
        $this->assertEquals($trialDaysRemaining, $responseObject->user_plan->start_trial_period);
    }


    public function testStartTrialPeriodForUserWithPartExpiredPremiumTrialBackOnBasic() {
        $trialDaysRemaining = rand(1, self::DEFAULT_TRIAL_PERIOD);
        $email = 'user-test-retention-of-trial-period@example.com';
        $password = 'password1';

        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));

        // Mock the fact that the Stripe customer.subscription.trial_end is
        // $trialDaysPassed days from now
        $this->getStripeService()->addResponseData('getCustomer', array(
            'subscription' => array(
                'trial_end' => time() + (86400 * $trialDaysRemaining)
            )
        ));

        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction($email, 'basic');

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());
        $this->assertEquals($trialDaysRemaining, $responseObject->user_plan->start_trial_period);
    }


    public function testGetTeamPlanForTeamMember() {
        $leader = $this->createAndActivateUser('leader@example.com', 'password');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $user = $this->createAndActivateUser('user@example.com', 'password');
        $this->getUserService()->setUser($user);

        $this->getTeamMemberService()->add($team, $user);

        $this->getUserAccountPlanService()->subscribe($leader, $this->getAccountPlanService()->find('agency'));;

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());

        $this->assertEquals('agency', $responseObject->user_plan->plan->name);
    }
}


