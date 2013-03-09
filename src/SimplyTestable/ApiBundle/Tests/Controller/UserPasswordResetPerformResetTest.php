<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

class UserPasswordResetPerformResetTest extends BaseControllerJsonTestCase {
    
    public function testPerformResetWithValidToken() {
        $this->resetSystemState();
        $email = 'user1@example.com';
        $password = 'password1';
        
        $user = $this->createAndActivateUser($email, $password);        
        $token = $this->getPasswordResetToken($user);
        
        $controller = $this->getUserPasswordResetController('resetPasswordAction', array(
            'password' => 'newpassword'
        ));
        
        $response = $controller->resetPasswordAction($token);
        $this->assertEquals(200, $response->getStatusCode()); 
    }
    
    
    public function testPerformResetWithInvalidToken() {
        $this->resetSystemState();
        $token = 'invalid token';
        
        $controller = $this->getUserPasswordResetController('resetPasswordAction', array(
            'password' => 'newpassword'
        ));
        
        try {
            $response = $controller->resetPasswordAction($token);
            $this->fail('Attempt to reset password with invalid token did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());            
        }         
    }     
    
    public function testPerformResetWithInactiveUser() {
        $this->resetSystemState();
        $email = 'user1@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);        
        $token = $this->getPasswordResetToken($user);
        
        $controller = $this->getUserPasswordResetController('resetPasswordAction', array(
            'password' => 'newpassword'
        ));
        
        $response = $controller->resetPasswordAction($token);
        $this->assertEquals(200, $response->getStatusCode());         
    }
    
    public function testPerformResetInMaintenanceReadOnlyModeReturns503() {
        $this->resetSystemState();
        $email = 'user1@example.com';
        $password = 'password1';
        
        $user = $this->createAndActivateUser($email, $password);        
        $token = $this->getPasswordResetToken($user);
        
        $controller = $this->getUserPasswordResetController('resetPasswordAction', array(
            'password' => 'newpassword'
        ));
        
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only')); 
        $this->assertEquals(503, $controller->resetPasswordAction($token)->getStatusCode());           
    }      
}