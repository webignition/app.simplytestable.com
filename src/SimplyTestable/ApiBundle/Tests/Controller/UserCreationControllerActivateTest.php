<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

class UserCreationControllerActivateTest extends BaseControllerJsonTestCase {
       

    public function testActivateActionWithCorrectToken() {
        $this->resetSystemState();
        $email = 'user1@example.com';
        $password = 'password1';        
        
        $this->createUser($email, $password);
        
        $user = $this->getUserService()->findUserByEmail($email);
        
        $this->assertNotNull($user);
        $this->assertInstanceOf('SimplyTestable\ApiBundle\Entity\User', $user);
        
        $controller = $this->getUserCreationController('activateAction');        
        $response = $controller->activateAction($user->getConfirmationToken());
        
        $this->assertEquals(200, $response->getStatusCode());        
    }    
    
    
    public function testActivateActionWithIncorrectToken() {
        $this->resetSystemState();
        $email = 'user1@example.com';
        $password = 'password1';
        
        $this->createUser($email, $password);
        
        $user = $this->getUserService()->findUserByEmail($email);
        
        $this->assertNotNull($user);
        $this->assertInstanceOf('SimplyTestable\ApiBundle\Entity\User', $user);        
        
        try {
            $controller = $this->getUserCreationController('activateAction');        
            $response = $controller->activateAction('invalid token');
            $this->fail('Attempt to activate with incorrect token did not generate HTTP 400');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(400, $exception->getStatusCode());            
        }        
    } 
    
    public function testActivateInMaintenanceReadOnlyModeReturns503() {
        $this->resetSystemState();
        $email = 'user1@example.com';
        $password = 'password1';        
        
        $this->createUser($email, $password);
        
        $user = $this->getUserService()->findUserByEmail($email);
        
        $this->assertNotNull($user);
        $this->assertInstanceOf('SimplyTestable\ApiBundle\Entity\User', $user);
        
        $controller = $this->getUserCreationController('activateAction');        
        
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));                 
        $this->assertEquals(503, $controller->activateAction($user->getConfirmationToken())->getStatusCode());           
    }    
    
}