<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

class UserControllerCreateTest extends BaseControllerJsonTestCase {
    

    public function testCreateActionWithEmailPresent() {
        $this->setupDatabase();
        $email = 'user1@example.com';
        
        $controller = $this->getUserController('createAction', array(
            'email' => $email           
        ));
        
        $response = $controller->createAction();
        
        $this->assertEquals(200, $response->getStatusCode());       
    }
        
    public function testCreateActionWithoutEmail() {
        $this->setupDatabase();
        
        try {
            $controller = $this->getUserController('createAction', array());
            $controller->createAction();
            $this->fail('Attempt to create user with no email address did not generate HTTP 400');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(400, $exception->getStatusCode());            
        }
    }   
}


