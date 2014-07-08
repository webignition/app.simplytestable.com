<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\UserAccountPlanSubsciption\Subscribe;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class SubscribeTest extends BaseControllerJsonTestCase {
    
    public function testBasicToBasic() {
        $email = 'user1@example.com';
        $password = 'password1';
        $newPlan = 'basic';
        
        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);        
        
        $response = $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction($email, $newPlan);        
        $this->assertEquals(200, $response->getStatusCode());
        
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);
        $this->assertEquals($newPlan, $userAccountPlan->getPlan()->getName());        
    }
    
    public function testBasicToPersonal() {
        $this->performCurrentPlanToNewPlan('basic', 'personal');      
    }
    
    public function testPersonalToBasic() {
        $this->performCurrentPlanToNewPlan('personal', 'basic');      
    }   
    
    public function testPersonalToAgency() {
        $this->performCurrentPlanToNewPlan('personal', 'agency');      
    }     
    
    
    private function performCurrentPlanToNewPlan($currentPlan, $newPlan) {
        $email = 'user-' . $currentPlan . '-to-' . $newPlan . '@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);
        
        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find($currentPlan));
        
        $response = $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction($email, $newPlan);        
        $this->assertEquals(200, $response->getStatusCode());
        
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);
        $this->assertEquals($newPlan, $userAccountPlan->getPlan()->getName());        
        
        if ($userAccountPlan->getPlan()->getIsPremium()) {
            $this->assertNotNull($userAccountPlan->getStripeCustomer());
        }
    }
    
    public function testPremiumToNonPremiumChangeRetainsStripeCustomerId() {
        $email = 'user1@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);
        
        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));
        
        $personalAccountPlanStripeCustomer = $this->getUserAccountPlanService()->getForUser($user)->getStripeCustomer();
        
        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('basic'));
        
        $this->assertEquals($personalAccountPlanStripeCustomer, $this->getUserAccountPlanService()->getForUser($user)->getStripeCustomer());      
    }

    
    public function testRetentionOfTrialPeriodWhenSwitchingPlansFromPremiumToPremium() {
        $trialDaysPassed = rand(1, 30);
        $email = 'user-test-retention-of-trial-period@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);       
        
        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));
        
        // Mock the fact that the Stripe customer.subscription.trial_end is
        // $trialDaysPassed days from now      
        $this->getStripeService()->addResponseData('getCustomer', array(
            'subscription' => array(
                'trial_end' => time() + (86400 * $trialDaysPassed)
            )
        )); 
      
        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction($email, 'agency');
        
        $this->assertEquals($trialDaysPassed, $this->getUserAccountPlanService()->getForUser($user)->getStartTrialPeriod());      
    }  
    
    public function testRetentionOfTrialPeriodWhenSwitchingPlansFromPremiumToFree() {
        $trialDaysPassed = rand(1, 30);
        $email = 'user-test-retention-of-trial-period@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);       
        
        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));
        
        // Mock the fact that the Stripe customer.subscription.trial_end is
        // $trialDaysPassed days from now      
        $this->getStripeService()->addResponseData('getCustomer', array(
            'subscription' => array(
                'trial_end' => time() + (86400 * $trialDaysPassed)
            )
        )); 
      
        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction($email, 'basic');
        
        $this->assertEquals($trialDaysPassed, $this->getUserAccountPlanService()->getForUser($user)->getStartTrialPeriod());      
    }  
    
    public function testRetentionOfTrialPeriodWhenSwitchingPlansFromPremiumToFreeToPremium() {
        $trialDaysPassed = 16;
        $email = 'user-test-retention-of-trial-period@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);       
        
        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));
        
        // Mock the fact that the Stripe customer.subscription.trial_end is
        // $trialDaysPassed days from now      
        $this->getStripeService()->addResponseData('getCustomer', array(
            'subscription' => array(
                'trial_end' => time() + (86400 * $trialDaysPassed)
            )
        )); 
      
        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction($email, 'basic');        
        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction($email, 'agency');//        
        
        $this->assertEquals($trialDaysPassed, $this->getUserAccountPlanService()->getForUser($user)->getStartTrialPeriod());
    }
    
    public function testStripeUserIsRetainedWhenSwitchingFromPremiumToFreeToPremium() {
        $email = 'user-'.md5(microtime(true)).'@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);       
        
        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));
        
        $initialStripeCustomerId = $this->getUserAccountPlanService()->getForUser($user)->getStripeCustomer();
      
        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction($email, 'basic');        
        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction($email, 'agency');
        
        $this->assertEquals($initialStripeCustomerId, $this->getUserAccountPlanService()->getForUser($user)->getStripeCustomer());
    }
}


