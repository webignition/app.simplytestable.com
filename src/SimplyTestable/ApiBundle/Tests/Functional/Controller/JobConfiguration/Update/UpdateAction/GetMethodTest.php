<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Update\UpdateAction;

class GetMethodTest extends UpdateTest {

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
        $this->getCurrentController()->$methodName(self::LABEL);
    }

}