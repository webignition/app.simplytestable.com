<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

class UserControllerIsEnabledTest extends BaseControllerJsonTestCase {
    

    public function testExistsWithNotEnabledUser() {
        $this->setupDatabase();
        $email = 'user1@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);
        
        $controller = $this->getUserController('existsAction');
        $response = $controller->existsAction($user->getEmail());
        
        $this->assertEquals(200, $response->getStatusCode());      
    }
    
    
    public function testExistsWithEnabledUser() {
        $this->setupDatabase();
        $email = 'user1@example.com';
        $password = 'password1';
        
        $user = $this->createAndActivateUser($email, $password);
        
        $controller = $this->getUserController('existsAction');
        $response = $controller->existsAction($user->getEmail());
        
        $this->assertEquals(200, $response->getStatusCode());      
    }
    
    
    public function testExistsWithNonExistentUser() {
        $this->setupDatabase();
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


