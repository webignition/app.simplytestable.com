<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Delete\DeleteAction;

class GetMethodTest extends DeleteTest {

    public function setUp() {
        parent::setUp();
        $this->getRouter()->getContext()->setMethod('GET');
    }


    public function testGetMethodIsNotAllowed() {
        $this->setExpectedException(
            'Symfony\Component\Routing\Exception\MethodNotAllowedException'
        );

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $methodName = $this->getActionNameFromRouter();
        $this->getCurrentController()->$methodName(self::LABEL);
    }

}