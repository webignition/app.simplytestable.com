<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\User\GetAction;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class GetActionStripeCustomerTest extends BaseControllerJsonTestCase {
    
    const DEFAULT_TRIAL_PERIOD = 30;  

    public function testForUserWithBasicPlan() {
        $email = 'user1@example.com';
        $password = 'password1';

        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());
        $this->assertFalse(isset($responseObject->stripe_customer));
    }

    public function testForUserWithPremiumPlan() {
        $email = 'user1@example.com';
        $password = 'password1';

        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());
        $this->assertTrue(isset($responseObject->stripe_customer));
    }

    public function testForUserWhereIsActiveIsZero() {
        $email = 'user1@example.com';
        $password = 'password1';

        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));

        $this->getUserAccountPlanService()->deactivateAllForUser($user);

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());
        $this->assertTrue(isset($responseObject->stripe_customer));
    }

    public function testForUserWhereIsActiveIsZeroAndUserHasMany() {
        $email = 'user1@example.com';
        $password = 'password1';

        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));
        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('agency'));

        $this->getUserAccountPlanService()->deactivateAllForUser($user);

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());
        $this->assertTrue(isset($responseObject->stripe_customer));
    }


    public function testForUserWithPreviousPremiumTrialBackOnBasic() {
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
        $this->assertTrue(isset($responseObject->stripe_customer));
    }


    public function testTeamMemberSummaryExcludesTeamStripeCustomer() {
        $leader = $this->createAndActivateUser('leader@example.com');
        $this->getUserService()->setUser($leader);

        $this->getUserAccountPlanService()->subscribe($leader, $this->getAccountPlanService()->find('personal'));

        $member = $this->createAndActivateUser('user@example.com');

        $team = $this->getTeamService()->create('Foo', $leader);

        $this->getTeamMemberService()->add($team, $member);

        $this->getUserService()->setUser($member);

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());
        $this->assertFalse(isset($responseObject->stripe_customer));

    }
}


