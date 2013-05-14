<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class CreateUserEmailChangeTest extends BaseControllerJsonTestCase {
       
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }    
    
    public function testWithNotEnabledUser() {
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
    }
    
    public function testWithNonExistentUser() {
        $email = 'user1@example.com';
        $controller = $this->getUserEmailChangeController('createAction');

        try {            
            $controller->createAction($email, 'new_email');
            $this->fail('Attempt to create for non-existent user did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());            
        }           
    }
    
    
    public function testWithInvalidNewEmail() {        
        $email = 'user1@example.com';
        $password = 'password1';

        $user = $this->createAndActivateUser($email, $password);          
        $this->getUserService()->setUser($user);
        $controller = $this->getUserEmailChangeController('createAction');
        
        try {            
            $controller->createAction($user->getEmail(), 'new_email');
            $this->fail('Attempt to create with invalid new email did not generate HTTP 400');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(400, $exception->getStatusCode());            
        }
    }    
    
    
    public function testWhereNewEmailIsExistingUser() {        
        $email1 = 'user1@example.com';
        $password1 = 'password1';

        $user1 = $this->createAndActivateUser($email1, $password1);            

        $email2 = 'user2@example.com';
        $password2 = 'password2';

        $user2 = $this->createAndActivateUser($email2, $password2);        
        
        $this->getUserService()->setUser($user1);
        
        $controller = $this->getUserEmailChangeController('createAction');
        
        try {           
            
            $controller->createAction($user1->getEmail(), $user2->getEmail());
            $this->fail('Attempt to create with email of existing user did not generate HTTP 409');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(409, $exception->getStatusCode());            
        }
    }
    
    
    public function testWhereNewEmailIsExistingEmailChangeRequestForDifferentUser() {
        $email1 = 'user1@example.com';
        $password1 = 'password1';

        $user1 = $this->createAndActivateUser($email1, $password1);        
        $this->getUserService()->setUser($user1);
        
        $controller = $this->getUserEmailChangeController('createAction');        
        $controller->createAction($user1->getEmail(), 'user1-new@example.com');
        
        $email2 = 'user2@example.com';
        $password2 = 'password2';

        $user2 = $this->createAndActivateUser($email2, $password2);
        
        $this->getUserService()->setUser($user2);
        
        try {
            $controller->createAction($user2->getEmail(), 'user1-new@example.com');

            $this->fail('Attempt to create with email of existing change request did not generate HTTP 409');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(409, $exception->getStatusCode());            
        }        
    }
    
    public function testWhereUserAlreadyHasEmailChangeRequest() {
        try {
            $email = 'user1@example.com';
            $password = 'password1';

            $user = $this->createAndActivateUser($email, $password);            
            $this->getUserService()->setUser($user);
            
            $controller = $this->getUserEmailChangeController('createAction');
            $controller->createAction($user->getEmail(), 'user1-new@example.com');
            $controller->createAction($user->getEmail(), 'user1-new@example.com');

            $this->fail('Attempt to create with email of existing change request did not generate HTTP 409');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(409, $exception->getStatusCode());            
        }        
    }    
    
    
    public function testForDifferentUser() {
        $email1 = 'user1@example.com';
        $password1 = 'password1';

        $user1 = $this->createAndActivateUser($email1, $password1);
        
        $email2 = 'user2@example.com';
        $password2 = 'password2';

        $user2 = $this->createAndActivateUser($email2, $password2);        
        
        $this->getUserService()->setUser($user1);
        
        try {            
            $controller = $this->getUserEmailChangeController('createAction');
            $controller->createAction($user2->getEmail(), 'user1-new@example.com');

            $this->fail('Attempt to create for different user did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());            
        }        
    }
    
    public function testForCorrectUser() {
 
        $email = 'user1@example.com';
        $password = 'password1';

        $user = $this->createAndActivateUser($email, $password);            
        $this->getUserService()->setUser($user);

        $controller = $this->getUserEmailChangeController('createAction');
        $response = $controller->createAction($user->getEmail(), 'user1-new@example.com');

        $this->assertEquals(200, $response->getStatusCode());
    }
    
}