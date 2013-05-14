<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class CancelUserEmailChangeTest extends BaseControllerJsonTestCase {
       
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }    
    
    public function testForDifferentUser() {
        $email1 = 'user1@example.com';
        $password1 = 'password1';

        $user1 = $this->createAndActivateUser($email1, $password1);
        
        $email2 = 'user2@example.com';
        $password2 = 'password2';

        $user2 = $this->createAndActivateUser($email2, $password2);        
        
        $this->getUserService()->setUser($user2);        
        $this->getUserEmailChangeController('createAction')->createAction($user2->getEmail(), 'user1-new@example.com');        
        
        $this->getUserService()->setUser($user1);
        
        try {            
            $this->getUserEmailChangeController('cancelAction')->cancelAction($user2->getEmail());

            $this->fail('Attempt to cancel for different user did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());            
        }        
    }
    
    public function testForCorrectUser() { 
        $email = 'user1@example.com';
        $password = 'password1';

        $user = $this->createAndActivateUser($email, $password);            
        $this->getUserService()->setUser($user);

        $this->getUserEmailChangeController('createAction')->createAction($user->getEmail(), 'user1-new@example.com');        
        $this->assertNotNull($this->getUserEmailChangeRequestService()->findByUser($user));
        
        $response = $this->getUserEmailChangeController('cancelAction')->cancelAction($user->getEmail());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($this->getUserEmailChangeRequestService()->findByUser($user));
    }
    
}