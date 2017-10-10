<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Delete\DeleteAction;

class GetMethodTest extends DeleteTest {

    protected function setUp() {
        parent::setUp();
        $this->getRouter()->getContext()->setMethod('GET');
    }


    public function testGetMethodIsNotAllowed() {
        $this->setExpectedException(
            'Symfony\Component\Routing\Exception\MethodNotAllowedException'
        );

        $userService = $this->container->get('simplytestable.services.userservice');
        $this->setUser($userService->getPublicUser());
        $methodName = $this->getActionNameFromRouter();
        $this->getCurrentController()->$methodName(1);
    }

}