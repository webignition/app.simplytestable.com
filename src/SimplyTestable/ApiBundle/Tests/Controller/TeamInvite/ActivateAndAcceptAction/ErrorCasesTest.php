<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\TeamInvite\ActivateAndAcceptAction;

use SimplyTestable\ApiBundle\Tests\Controller\TeamInvite\ActionTest;

class ErrorCasesTest extends ActionTest {

    public function testInvalidTokenReturnsBadRequest() {
        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController()->$methodName(
            $this->container->get('request')
        );

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(1, $response->headers->get('X-TeamInviteActivateAndAccept-Error-Code'));
        $this->assertEquals('No invite for token', $response->headers->get('X-TeamInviteActivateAndAccept-Error-Message'));
    }
    
}