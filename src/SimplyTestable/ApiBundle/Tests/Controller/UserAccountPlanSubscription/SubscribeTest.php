<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class SubscribeTest extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }
    
//    public function testWithPublicUser() {
//        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
//        
//        $response = $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction('', '');        
//        $this->assertEquals(400, $response->getStatusCode());        
//    }
//    
//    public function testWithWrongUser() {
//        $email = 'user1@example.com';
//        $password = 'password1';
//        
//        $user = $this->createAndFindUser($email, $password);
//        $this->getUserService()->setUser($user);        
//        
//        $response = $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction('', '');        
//        $this->assertEquals(400, $response->getStatusCode());          
//    }
//    
//    public function testWithCorrectUserAndInvalidPlan() {
//        $email = 'user1@example.com';
//        $password = 'password1';
//        
//        $user = $this->createAndFindUser($email, $password);
//        $this->getUserService()->setUser($user);        
//        
//        $response = $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction($email, 'invalid-plan');        
//        $this->assertEquals(400, $response->getStatusCode());          
//    } 
    
    public function testWithCorrectUserAndValidPlan() {
        $email = 'user1@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);        
        
        $response = $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction($email, 'personal-9');        
        $this->assertEquals(400, $response->getStatusCode());          
    } 
    
/**
        $email = 'user1@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);
        
        $controller = $this->getUserEmailChangeController('createAction');
        
        try {            
            $controller->createAction($user->getEmail(), 'new_email');
            $this->fail('Attempt to create for not-enabled user did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());            
        }
 */    
    

//    public function testCreateActionWithEmailPresent() {
//        $email = 'user1@example.com';
//        $password = 'password';
//        
//        $controller = $this->getUserAccountPlanSubscriptionController('subscribeAction', array(
//            'email' => $email,
//            'password' => $password
//        ));
//        
//        $response = $controller->subscribeAction();
//        
//        $this->assertEquals(200, $response->getStatusCode());       
//    }
//
//    public function testCreateActionWithoutCredentials() {        
//        try {
//            $controller = $this->getUserAccountPlanSubscriptionController('subscribeAction', array());
//            $controller->subscribeAction();
//            $this->fail('Attempt to create user with no email address did not generate HTTP 400');
//        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
//            $this->assertEquals(400, $exception->getStatusCode());            
//        }
//    }     
//    
//    public function testCreateActionWithoutEmail() {        
//        try {
//            $controller = $this->getUserAccountPlanSubscriptionController('subscribeAction', array(
//                'password' => 'password'
//            ));
//            $controller->subscribeAction();
//            $this->fail('Attempt to create user with no email address did not generate HTTP 400');
//        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
//            $this->assertEquals(400, $exception->getStatusCode());            
//        }
//    }   
//    
//    
//    public function testCreateActionWithoutPassword() {        
//        try {
//            $controller = $this->getUserAccountPlanSubscriptionController('subscribeAction', array(
//                'email' => 'email'
//            ));
//            $controller->subscribeAction();
//            $this->fail('Attempt to create user with no email address did not generate HTTP 400');
//        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
//            $this->assertEquals(400, $exception->getStatusCode());            
//        }
//    }     
//    
//    public function testCreateWithEmailOfExistingNotEnabledUser() {
//        $email = 'user1@example.com';
//        $password = 'password1';
//        $this->createAndFindUser($email, $password);
//        
//        $controller = $this->getUserAccountPlanSubscriptionController('subscribeAction', array(
//            'email' => $email,
//            'password' => $password
//        ));
//        
//        $response = $controller->subscribeAction();
//        
//        $this->assertEquals(200, $response->getStatusCode());          
//    }  
//    
//    
//    public function testCreateWithEmailOfExistingEnabledUser() {
//        $email = 'user1@example.com';
//        $password = 'password1';        
//        $this->createAndActivateUser($email, $password);
//        
//        $controller = $this->getUserAccountPlanSubscriptionController('subscribeAction', array(
//            'email' => $email,
//            'password' => $password
//        ));
//        
//        $response = $controller->subscribeAction();
//        
//        $this->assertEquals(302, $response->getStatusCode());          
//    }  
//    
//    
//    public function testCreateInMaintenanceReadOnlyModeReturns503() {
//        $email = 'user1@example.com';
//        $password = 'password';
//        
//        $controller = $this->getUserAccountPlanSubscriptionController('subscribeAction', array(
//            'email' => $email,
//            'password' => $password
//        ));
//        
//        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));        
//        $this->assertEquals(503, $controller->subscribeAction()->getStatusCode());            
//    }
//    
//    public function testWithEmailAndPasswordCreatesUser() {        
//        $email = 'user1@example.com';
//        $password = 'password1';        
//        
//        $this->assertNull($this->getUserService()->findUserByEmail($email));
//        
//        $controller = $this->getUserAccountPlanSubscriptionController('subscribeAction', array(
//            'email' => $email,
//            'password' => $password
//        ));
//        
//        $response = $controller->subscribeAction();
//        
//        $this->assertEquals(200, $response->getStatusCode()); 
//        
//        $user = $this->getUserService()->findUserByEmail($email);
//        $this->assertInstanceOf('SimplyTestable\ApiBundle\Entity\User', $user);
//    }    
//    
//    
//    public function testSuccessfulCreationAddsUserToBasicPlan() {        
//        $email = 'user1@example.com';
//        $password = 'password1';        
//        
//        $this->createAndActivateUser($email, $password);
//
//        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($this->getUserService()->findUserByEmail($email));
//        $this->assertEquals('basic', $userAccountPlan->getPlan()->getName());
//    }
    
    public function testActivateInMaintenanceReadOnlyModeReturns503() {
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));                 
        $this->assertEquals(503, $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction('', '')->getStatusCode());           
    }     
}


