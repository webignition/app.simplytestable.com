<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class SubscribeTest extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }
    
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
    
    
    public function testActivateInMaintenanceReadOnlyModeReturns503() {
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));                 
        $this->assertEquals(503, $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction('', '')->getStatusCode());           
    }     
}


