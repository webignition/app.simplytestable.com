<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

class UserPasswordResetPerformResetTest extends BaseControllerJsonTestCase {
    
    public function testPerformResetWithValidToken() {
        $this->setupDatabase();
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
        $this->setupDatabase();
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
}


