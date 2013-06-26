<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class AssociateCardTest extends BaseControllerJsonTestCase {

    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }
    
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
    
    
    public function testWithTokenForCardFailingCheck() {
        $email = 'jon@simplytestable.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);
        
        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction($email, 'personal');
        
        $this->getStripeService()->setIssueStripeCardError(true);
        
        $response = $this->getUserAccountPlanSubscriptionController('associateCardAction')->associateCardAction($email, $this->generateStripeCardToken());        
        $this->assertEquals(400, $response->getStatusCode());        
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


