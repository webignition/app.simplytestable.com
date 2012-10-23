<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

class UserControllerActivateTest extends BaseControllerJsonTestCase {
       

    public function testActivateActionWithCorrectToken() {
        $this->setupDatabase();
        $email = 'user1@example.com';
        
        $this->createUser($email);
        
        $user = $this->getUserService()->findUserByEmail($email);
        
        $this->assertNotNull($user);
        $this->assertInstanceOf('SimplyTestable\ApiBundle\Entity\User', $user);
        
        $controller = $this->getUserController('activateAction');        
        $response = $controller->activateAction($user->getConfirmationToken());
        
        $this->assertEquals(200, $response->getStatusCode());        
    }    
    
    
    public function testActivateActionWithIncorrectToken() {
        $this->setupDatabase();
        $email = 'user1@example.com';
        
        $this->createUser($email);
        
        $user = $this->getUserService()->findUserByEmail($email);
        
        $this->assertNotNull($user);
        $this->assertInstanceOf('SimplyTestable\ApiBundle\Entity\User', $user);        
        
        try {
            $controller = $this->getUserController('activateAction');        
            $response = $controller->activateAction('invalid token');
            $this->fail('Attempt to activate with incorrect token did not generate HTTP 400');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(400, $exception->getStatusCode());            
        }        
    }     
    
}


