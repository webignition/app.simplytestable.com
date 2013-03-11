<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

class UserCreationControllerCreateTest extends BaseControllerJsonTestCase {
    

    public function testCreateActionWithEmailPresent() {
        $this->resetSystemState();
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
        $this->resetSystemState();
        
        try {
            $controller = $this->getUserCreationController('createAction', array());
            $controller->createAction();
            $this->fail('Attempt to create user with no email address did not generate HTTP 400');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(400, $exception->getStatusCode());            
        }
    }     
    
    public function testCreateActionWithoutEmail() {
        $this->resetSystemState();
        
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
        $this->resetSystemState();
        
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
        $this->resetSystemState(); 
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
        $this->resetSystemState();     
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


