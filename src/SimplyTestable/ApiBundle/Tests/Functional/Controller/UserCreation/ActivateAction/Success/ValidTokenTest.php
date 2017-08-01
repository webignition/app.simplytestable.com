<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\UserCreation\ActivateAction\Success;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class ValidTokenTest extends SuccessTest {

    public function setUp() {
        parent::setUp();

        $userFactory = new UserFactory($this->container);

        $this->user = $userFactory->create();

        $methodName = $this->getActionNameFromRouter();
        $this->response = $this->getCurrentController()->$methodName($this->user->getConfirmationToken());
    }


}

