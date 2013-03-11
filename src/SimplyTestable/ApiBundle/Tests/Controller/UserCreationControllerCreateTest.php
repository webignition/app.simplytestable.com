<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

class UserCreationControllerCreateTest extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }        
    

    public function testCreateActionWithEmailPresent() {
        $this->removeAllUsers();
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
        $this->removeAllUsers();
        
        try {
            $controller = $this->getUserCreationController('createAction', array());
            $controller->createAction();
            $this->fail('Attempt to create user with no email address did not generate HTTP 400');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(400, $exception->getStatusCode());            
        }
    }     
    
    public function testCreateActionWithoutEmail() {
        $this->removeAllUsers();
        
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
        $this->removeAllUsers();
        
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
        $this->removeAllUsers(); 
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
        $this->removeAllUsers();     
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
}


