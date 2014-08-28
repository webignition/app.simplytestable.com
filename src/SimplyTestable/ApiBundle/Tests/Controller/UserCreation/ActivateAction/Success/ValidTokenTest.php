<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\UserCreation\ActivateAction\Success;

class ValidTokenTest extends SuccessTest {

    public function setUp() {
        parent::setUp();

        $email = 'user1@example.com';
        $password = 'password1';

        $this->createUser($email, $password);

        $this->user = $this->getUserService()->findUserByEmail($email);

        $methodName = $this->getActionNameFromRouter();
        $this->response = $this->getCurrentController()->$methodName($this->user->getConfirmationToken());
    }

    
}

