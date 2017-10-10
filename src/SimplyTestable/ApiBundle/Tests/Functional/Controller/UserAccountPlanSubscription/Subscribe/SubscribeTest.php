<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\UserAccountPlanSubsciption\Subscribe;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;

class SubscribeTest extends BaseControllerJsonTestCase
{
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

    public function testBasicToBasic()
    {
        $newPlan = 'basic';

        $user = $this->userFactory->create();
        $this->setUser($user);

        $response = $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction(
            $user->getEmail(),
            $newPlan
        );
        $this->assertEquals(200, $response->getStatusCode());

        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);
        $this->assertEquals($newPlan, $userAccountPlan->getPlan()->getName());
    }

    public function testBasicToPersonal()
    {
        $this->performCurrentPlanToNewPlan('basic', 'personal');
    }

    public function testPersonalToBasic()
    {
        $this->performCurrentPlanToNewPlan('personal', 'basic');
    }

    public function testPersonalToAgency()
    {
        $this->performCurrentPlanToNewPlan('personal', 'agency');
    }

    private function performCurrentPlanToNewPlan($currentPlan, $newPlan)
    {
        $email = 'user-' . $currentPlan . '-to-' . $newPlan . '@example.com';

        $user = $this->userFactory->create([
            UserFactory::KEY_EMAIL => $email,
        ]);
        $this->setUser($user);

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find($currentPlan));

        $response = $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction(
            $email,
            $newPlan
        );
        $this->assertEquals(200, $response->getStatusCode());

        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);
        $this->assertEquals($newPlan, $userAccountPlan->getPlan()->getName());

        if ($userAccountPlan->getPlan()->getIsPremium()) {
            $this->assertNotNull($userAccountPlan->getStripeCustomer());
        }
    }

    public function testPremiumToNonPremiumChangeRetainsStripeCustomerId()
    {
        $user = $this->userFactory->create();
        $this->setUser($user);

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));

        $personalAccountPlanStripeCustomer = $this->getUserAccountPlanService()->getForUser($user)->getStripeCustomer();

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('basic'));

        $this->assertEquals(
            $personalAccountPlanStripeCustomer,
            $this->getUserAccountPlanService()->getForUser($user)->getStripeCustomer()
        );
    }

    public function testRetentionOfTrialPeriodWhenSwitchingPlansFromPremiumToPremium()
    {
        $trialDaysPassed = rand(1, 30);

        $user = $this->userFactory->create();
        $this->setUser($user);

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));

        // Mock the fact that the Stripe customer.subscription.trial_end is
        // $trialDaysPassed days from now
        $this->getStripeService()->addResponseData('getCustomer', array(
            'subscription' => array(
                'trial_end' => time() + (86400 * $trialDaysPassed)
            )
        ));

        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction(
            $user->getEmail(),
            'agency'
        );

        $this->assertEquals(
            $trialDaysPassed,
            $this->getUserAccountPlanService()->getForUser($user)->getStartTrialPeriod()
        );
    }

    public function testRetentionOfTrialPeriodWhenSwitchingPlansFromPremiumToFree()
    {
        $trialDaysPassed = rand(1, 30);

        $user = $this->userFactory->create();
        $this->setUser($user);

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));

        // Mock the fact that the Stripe customer.subscription.trial_end is
        // $trialDaysPassed days from now
        $this->getStripeService()->addResponseData('getCustomer', array(
            'subscription' => array(
                'trial_end' => time() + (86400 * $trialDaysPassed)
            )
        ));

        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction(
            $user->getEmail(),
            'basic'
        );

        $this->assertEquals(
            $trialDaysPassed,
            $this->getUserAccountPlanService()->getForUser($user)->getStartTrialPeriod()
        );
    }

    public function testRetentionOfTrialPeriodWhenSwitchingPlansFromPremiumToFreeToPremium()
    {
        $trialDaysPassed = 16;

        $user = $this->userFactory->create();
        $this->setUser($user);

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));

        // Mock the fact that the Stripe customer.subscription.trial_end is
        // $trialDaysPassed days from now
        $this->getStripeService()->addResponseData('getCustomer', array(
            'subscription' => array(
                'trial_end' => time() + (86400 * $trialDaysPassed)
            )
        ));

        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction(
            $user->getEmail(),
            'basic'
        );

        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction(
            $user->getEmail(),
            'agency'
        );

        $this->assertEquals(
            $trialDaysPassed,
            $this->getUserAccountPlanService()->getForUser($user)->getStartTrialPeriod()
        );
    }

    public function testStripeUserIsRetainedWhenSwitchingFromPremiumToFreeToPremium()
    {
        $user = $this->userFactory->create();
        $this->setUser($user);

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));

        $initialStripeCustomerId = $this->getUserAccountPlanService()->getForUser($user)->getStripeCustomer();

        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction(
            $user->getEmail(),
            'basic'
        );

        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction(
            $user->getEmail(),
            'agency'
        );

        $this->assertEquals(
            $initialStripeCustomerId,
            $this->getUserAccountPlanService()->getForUser($user)->getStripeCustomer()
        );
    }
}
