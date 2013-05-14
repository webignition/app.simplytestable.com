<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class GetUserEmailChangeTest extends BaseControllerJsonTestCase {
       
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }
    
    public function testWithNonExistentUser() {
        $email = 'user1@example.com';
        $controller = $this->getUserEmailChangeController('getAction');

        try {            
            $controller->getAction($email);
            $this->fail('Attempt to get for non-existent user did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());            
        }           
    }
    
    public function testWithValidUserThatHasNoEmailChangeRequest() {
        $email = 'user1@example.com';
        $password = 'password1';

        $this->createAndActivateUser($email, $password);            
        
        $controller = $this->getUserEmailChangeController('getAction');
        
        try {            
            $controller->getAction($email);
            $this->fail('Attempt to get where user does not have an email change request did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());            
        }           
    }
    
    
    public function testWithValidUserAndThatHasEmailChangeRequest() {
        $email = 'user1@example.com';
        $password = 'password1';
        $newEmail = 'user1-new@example.com';

        $user = $this->createAndActivateUser($email, $password);            
        $this->getUserService()->setUser($user);

        $this->getUserEmailChangeController('createAction')->createAction($user->getEmail(), $newEmail);
          
        $response = $this->getUserEmailChangeController('getAction')->getAction($user->getEmail());      

        $this->assertEquals(200, $response->getStatusCode());
        
        $responseObject = json_decode($response->getContent());
        
        $this->assertEquals($email, $responseObject->user);
        $this->assertEquals($newEmail, $responseObject->new_email);
        $this->assertNotNull($responseObject->token);
    }
    
}