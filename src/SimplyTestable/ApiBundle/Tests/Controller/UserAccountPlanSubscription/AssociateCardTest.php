<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\UserAccountPlanSubsciption;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class AssociateCardTest extends BaseControllerJsonTestCase {
    
    public function testWithPublicUser() {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        
        $response = $this->getUserAccountPlanSubscriptionController('associateCardAction')->associateCardAction('', '');        
        $this->assertEquals(400, $response->getStatusCode());        
    }
    
    public function testWithWrongUser() {
        $email = 'user1@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);        
        
        $response = $this->getUserAccountPlanSubscriptionController('associateCardAction')->associateCardAction('', '');        
        $this->assertEquals(400, $response->getStatusCode());          
    }
    
    public function testWithInvalidStripeCardToken() {
        $email = 'user1@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);        
        
        $response = $this->getUserAccountPlanSubscriptionController('associateCardAction')->associateCardAction($email, '');        
        $this->assertEquals(400, $response->getStatusCode());          
    }    
    
    public function testWithNoStripeCustomer() {
        $email = 'user1@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);        
        
        $response = $this->getUserAccountPlanSubscriptionController('associateCardAction')->associateCardAction($email, 'tok_22SBwowh6VeVgR');        
        $this->assertEquals(400, $response->getStatusCode());          
    }
    
    public function testWithValidStripeCustomerandValidStipeCardToken() {        
        $email = 'user1@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);        
        
        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction($email, 'personal');
        
        $response = $this->getUserAccountPlanSubscriptionController('associateCardAction')->associateCardAction($email, $this->generateStripeCardToken());        
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testWithTokenForCardFailingZipCheck() {
        $email = 'jon@simplytestable.com';
        $password = 'password1';
        $stripeErrorMessage = 'The zip code you supplied failed validation.';
        $stripeErrorParam = 'address_zip';
        $stripeErrorCode = 'incorrect_zip';
        
        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);
        
        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction($email, 'personal');
        
        $this->getStripeService()->setIssueStripeCardError(true);
        $this->getStripeService()->setNextStripeCardErrorMessage($stripeErrorMessage);
        $this->getStripeService()->setNextStripeCardErrorParam($stripeErrorParam);
        $this->getStripeService()->setNextStripeCardErrorCode($stripeErrorCode);
        
        $response = $this->getUserAccountPlanSubscriptionController('associateCardAction')->associateCardAction($email, $this->generateStripeCardToken());
        $this->assertEquals(400, $response->getStatusCode());        
        
        $this->assertEquals($stripeErrorMessage, $response->headers->get('X-Stripe-Error-Message'));
        $this->assertEquals($stripeErrorParam, $response->headers->get('X-Stripe-Error-Param'));
        $this->assertEquals($stripeErrorCode, $response->headers->get('X-Stripe-Error-Code'));
    }    
    
    
    public function testWithTokenForCardFailingCvcCheck() {
        $email = 'jon@simplytestable.com';
        $password = 'password1';
        $stripeErrorMessage = 'Your card\'s security code is incorrect.';
        $stripeErrorParam = 'cvc';
        $stripeErrorCode = 'incorrect_cvc';
        
        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);
        
        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction($email, 'personal');
        
        $this->getStripeService()->setIssueStripeCardError(true);
        $this->getStripeService()->setNextStripeCardErrorMessage($stripeErrorMessage);
        $this->getStripeService()->setNextStripeCardErrorParam($stripeErrorParam);
        $this->getStripeService()->setNextStripeCardErrorCode($stripeErrorCode);
        
        $response = $this->getUserAccountPlanSubscriptionController('associateCardAction')->associateCardAction($email, $this->generateStripeCardToken());
        $this->assertEquals(400, $response->getStatusCode());        
        
        $this->assertEquals($stripeErrorMessage, $response->headers->get('X-Stripe-Error-Message'));
        $this->assertEquals($stripeErrorParam, $response->headers->get('X-Stripe-Error-Param'));
        $this->assertEquals($stripeErrorCode, $response->headers->get('X-Stripe-Error-Code'));
    }

    
    private function generateStripeCardToken() {
        return 'tok_' . $this->generateAlphaNumericToken(14);
    }
    
    private function generateAlphaNumericToken($length) {
        $token = '';
        
        while (strlen($token) < $length) {            
            $token .= (rand(0, 1) === 0) ? $this->generateRandomNumericCharacter() : $this->generateRandomAlphaCharacter();
        }
        
        return $token;
    }
    
    private function generateRandomNumericCharacter() {
        return (string)rand(0,9);
    }
    
    private function generateRandomAlphaCharacter() {
        $character = chr(rand(65, 90));
        return (rand(0, 1) === 1) ? $character : strtolower($character);
        
    }
}


