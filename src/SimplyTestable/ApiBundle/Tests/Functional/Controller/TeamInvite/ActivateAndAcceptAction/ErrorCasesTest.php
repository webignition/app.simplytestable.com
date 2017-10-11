<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\TeamInvite\ActivateAndAcceptAction;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;

class ErrorCasesTest extends BaseControllerJsonTestCase {

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