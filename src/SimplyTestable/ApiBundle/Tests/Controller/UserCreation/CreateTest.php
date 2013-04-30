<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class CreateTest extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }        
    

    public function testCreateActionWithEmailPresent() {
        $email = 'user1@example.com';
        $password = 'password';
        
        $controller = $this->getUserCreationController('createAction', array(
            'email' => $email,
            'password' => $password
        ));
        
        $response = $controller->createAction();
        
        $this->assertEquals(200, $response->getStatusCode());       
    }

    public function testCreateActionWithoutCredentials() {        
        try {
            $controller = $this->getUserCreationController('createAction', array());
            $controller->createAction();
            $this->fail('Attempt to create user with no email address did not generate HTTP 400');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(400, $exception->getStatusCode());            
        }
    }     
    
    public function testCreateActionWithoutEmail() {        
        try {
            $controller = $this->getUserCreationController('createAction', array(
                'password' => 'password'
            ));
            $controller->createAction();
            $this->fail('Attempt to create user with no email address did not generate HTTP 400');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(400, $exception->getStatusCode());            
        }
    }   
    
    
    public function testCreateActionWithoutPassword() {        
        try {
            $controller = $this->getUserCreationController('createAction', array(
                'email' => 'email'
            ));
            $controller->createAction();
            $this->fail('Attempt to create user with no email address did not generate HTTP 400');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(400, $exception->getStatusCode());            
        }
    }     
    
    public function testCreateWithEmailOfExistingNotEnabledUser() {
        $email = 'user1@example.com';
        $password = 'password1';
        $this->createAndFindUser($email, $password);
        
        $controller = $this->getUserCreationController('createAction', array(
            'email' => $email,
            'password' => $password
        ));
        
        $response = $controller->createAction();
        
        $this->assertEquals(200, $response->getStatusCode());          
    }  
    
    
    public function testCreateWithEmailOfExistingEnabledUser() {
        $email = 'user1@example.com';
        $password = 'password1';        
        $this->createAndActivateUser($email, $password);
        
        $controller = $this->getUserCreationController('createAction', array(
            'email' => $email,
            'password' => $password
        ));
        
        $response = $controller->createAction();
        
        $this->assertEquals(302, $response->getStatusCode());          
    }  
    
    
    public function testCreateInMaintenanceReadOnlyModeReturns503() {
        $email = 'user1@example.com';
        $password = 'password';
        
        $controller = $this->getUserCreationController('createAction', array(
            'email' => $email,
            'password' => $password
        ));
        
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));        
        $this->assertEquals(503, $controller->createAction()->getStatusCode());            
    }
    
    public function testWithEmailAndPasswordCreatesUser() {        
        $email = 'user1@example.com';
        $password = 'password1';        
        
        $this->assertNull($this->getUserService()->findUserByEmail($email));
        
        $controller = $this->getUserCreationController('createAction', array(
            'email' => $email,
            'password' => $password
        ));
        
        $response = $controller->createAction();
        
        $this->assertEquals(200, $response->getStatusCode()); 
        
        $user = $this->getUserService()->findUserByEmail($email);
        $this->assertInstanceOf('SimplyTestable\ApiBundle\Entity\User', $user);
    }    
    
    
    public function testSuccessfulCreationAddsUserToBasicPlan() {        
        $email = 'user1@example.com';
        $password = 'password1';        
        
        $this->createAndActivateUser($email, $password);

        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($this->getUserService()->findUserByEmail($email));
        $this->assertEquals('basic', $userAccountPlan->getPlan()->getName());
    }
}


