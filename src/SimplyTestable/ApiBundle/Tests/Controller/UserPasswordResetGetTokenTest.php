<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

class UserPasswordResetGetTokenTest extends BaseControllerJsonTestCase {
    

    public function testGetTokenWithNotEnabledUser() {
        $this->setupDatabase();
        $email = 'user1@example.com';
        
        $this->createUser($email);
        
        try {
            $controller = $this->getUserPasswordResetController('getTokenAction');
            $controller->getTokenAction($email);
            $this->fail('Attempt to get token for not-enabled user did not generate HTTP 403');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(403, $exception->getStatusCode());            
        }             
    }
        
    public function testGetTokenWithNonExistentUser() {
        $this->setupDatabase();
        $email = 'user1@example.com';
        
        try {
            $controller = $this->getUserPasswordResetController('getTokenAction');
            $controller->getTokenAction($email);
            $this->fail('Attempt to get token for non-existent user did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());            
        }             
    } 
    
    
    public function testGetTokenWithEnabledUser() {
        $this->setupDatabase();
        $email = 'user1@example.com';
        
        $user = $this->createAndActivateUser($email);
        
        $controller = $this->getUserPasswordResetController('getTokenAction');
        $response = $controller->getTokenAction($user->getEmail());
        
        $token = $this->getUserService()->getConfirmationToken($user);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($token, json_decode($response->getContent()));             
    }     
}


