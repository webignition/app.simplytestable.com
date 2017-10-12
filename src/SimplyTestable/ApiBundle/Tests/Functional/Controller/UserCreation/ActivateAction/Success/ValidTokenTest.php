<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\UserCreation\ActivateAction\Success;

use SimplyTestable\ApiBundle\Controller\UserCreationController;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class ValidTokenTest extends SuccessTest {

    protected function setUp() {
        parent::setUp();

        $userFactory = new UserFactory($this->container);

        $this->user = $userFactory->create();

        $userCreationController = new UserCreationController();
        $userCreationController->setContainer($this->container);

        $this->response = $userCreationController->activateAction($this->user->getConfirmationToken());
    }


}

