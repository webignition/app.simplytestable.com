<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\UserCreation;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class ActivateTest extends BaseControllerJsonTestCase {   

    public function testActivateActionWithCorrectToken() {
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
        $this->executeCommand('simplytestable:maintenance:enable-read-only');                 
        $this->assertEquals(503, $this->getUserCreationController('activateAction')->activateAction('')->getStatusCode());           
    }

    public function testPasswordInRequestChangesPasswordAtActivationTime() {
        $email = 'user1@example.com';
        $initialPassword = 'password1';
        $updatedPassword = 'password2';

        $user = $this->createAndFindUser($email, $initialPassword);
        $initialEncodedPassword = $user->getPassword();

        $this->getUserCreationController('activateAction', [
            'password' => $updatedPassword
        ])->activateAction($user->getConfirmationToken());

        $updatedEncodedPassword = $user->getPassword();

        $this->assertFalse($initialEncodedPassword == $updatedEncodedPassword);
    }


    public function testNoPasswordInRequestDoesNotChangePasswordAtActivationTime() {
        $user = $this->createAndFindUser('user1@example.com');
        $initialEncodedPassword = $user->getPassword();

        $this->getUserCreationController('activateAction')->activateAction($user->getConfirmationToken());

        $updatedEncodedPassword = $user->getPassword();

        $this->assertTrue($initialEncodedPassword == $updatedEncodedPassword);
    }
    
}