<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class ExistsTest extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }    

    public function testExistsWithNotEnabledUser() {
        $this->removeAllUsers();

        $email = 'user1@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);
        
        $controller = $this->getUserController('existsAction');
        $response = $controller->existsAction($user->getEmail());
        
        $this->assertEquals(200, $response->getStatusCode());      
    }
    
    
    public function testExistsWithEnabledUser() {
        $this->removeAllUsers();

        $email = 'user1@example.com';        
        $password = 'password1';
                
        $user = $this->createAndActivateUser($email, $password);
        
        $controller = $this->getUserController('existsAction');
        $response = $controller->existsAction($user->getEmail());
        
        $this->assertEquals(200, $response->getStatusCode());      
    }
    
    
    public function testExistsWithNonExistentUser() {
        $this->removeAllUsers();

        $email = 'user1@example.com';
                
        try {
            $controller = $this->getUserController('existsAction');
            $response = $controller->existsAction($email);
            $this->fail('Attempt to check existence for non-existent user did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());            
        }  
    }     
}


