<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\UserAccountPlanSubsciption;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class SubscribeTest extends BaseControllerJsonTestCase {

    public function testWithPublicUser() {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        
        $response = $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction('', '');        
        $this->assertEquals(400, $response->getStatusCode());        
    }
    
    public function testWithWrongUser() {
        $email = 'user1@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);        
        
        $response = $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction('', '');        
        $this->assertEquals(400, $response->getStatusCode());          
    }
    
    public function testWithCorrectUserAndInvalidPlan() {
        $email = 'user1@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);        
        
        $response = $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction($email, 'invalid-plan');        
        $this->assertEquals(400, $response->getStatusCode());          
    } 
    
    public function testWithInvalidStripeApiKey() {
        $email = 'invalid-api-key@example.com';
        $password = 'password1';
        $newPlan = 'personal';
        
        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user); 
        
        $this->getStripeService()->setHasInvalidApiKey(true);
        
        $response = $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction($email, $newPlan);        
        $this->assertEquals(403, $response->getStatusCode());        
    }
    
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
    
    public function testActivateInMaintenanceReadOnlyModeReturns503() {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');               
        $this->assertEquals(503, $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction('', '')->getStatusCode());           
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
    
    public function testSubscribeWithUserThatHasDecliningCard() {
        $email = 'user1@example.com';
        $password = 'password1';
        $currentPlan = 'basic';
        $newPlan = 'personal';
        
        $stripeErrorMessage = 'Your card was declined.';
        $stripeErrorParam = null;
        $stripeErrorCode = 'card_declined';
        
        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);    
        
        $this->getStripeService()->setIssueStripeCardError(true);
        $this->getStripeService()->setNextStripeCardErrorMessage($stripeErrorMessage);
        $this->getStripeService()->setNextStripeCardErrorParam($stripeErrorParam);
        $this->getStripeService()->setNextStripeCardErrorCode($stripeErrorCode);        
        
        $response = $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction($email, $newPlan);        
        $this->assertEquals(400, $response->getStatusCode());
        
        $this->assertEquals($stripeErrorMessage, $response->headers->get('X-Stripe-Error-Message'));  
        $this->assertEquals($stripeErrorCode, $response->headers->get('X-Stripe-Error-Code'));        
        
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);
        $this->assertEquals($currentPlan, $userAccountPlan->getPlan()->getName());
    }
}


