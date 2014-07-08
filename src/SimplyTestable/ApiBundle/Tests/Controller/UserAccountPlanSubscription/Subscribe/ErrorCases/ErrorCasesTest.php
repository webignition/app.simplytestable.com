<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\UserAccountPlanSubsciption\Subscribe\ErrorCases;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class ErrorCasesTest extends BaseControllerJsonTestCase {

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

    public function testActivateInMaintenanceReadOnlyModeReturns503() {

        $this->executeCommand('simplytestable:maintenance:enable-read-only');               
        $this->assertEquals(503, $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction('', '')->getStatusCode());           
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


