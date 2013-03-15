<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class GetTokenTest extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }    

    public function testGetTokenWithNotEnabledUser() {
        $this->removeAllUsers();

        $email = 'user1@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);
        
        $controller = $this->getUserController('getTokenAction');
        $response = $controller->getTokenAction($user->getEmail());
        
        $token = $this->getUserService()->getConfirmationToken($user);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($token, json_decode($response->getContent()));            
    }
        
    public function testGetTokenWithNonExistentUser() {
        $this->removeAllUsers();

        $email = 'user1@example.com';
        
        try {
            $controller = $this->getUserController('getTokenAction');
            $controller->getTokenAction($email);
            $this->fail('Attempt to get token for non-existent user did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());            
        }             
    } 
    
    
    public function testGetTokenWithEnabledUser() {
        $this->removeAllUsers();

        $email = 'user1@example.com';
        $password = 'password1';
        
        $user = $this->createAndActivateUser($email, $password);
        
        $controller = $this->getUserController('getTokenAction');
        $response = $controller->getTokenAction($user->getEmail());
        
        $token = $this->getUserService()->getConfirmationToken($user);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($token, json_decode($response->getContent()));             
    }     
}


