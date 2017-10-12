<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\TeamInvite\ActivateAndAcceptAction;

use SimplyTestable\ApiBundle\Controller\TeamInviteController;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;
use Symfony\Component\HttpFoundation\Request;

class ErrorCasesTest extends BaseControllerJsonTestCase {

    public function testInvalidTokenReturnsBadRequest() {
        $controller = new TeamInviteController();
        $controller->setContainer($this->container);

        $request = new Request([], ['token' => 'foo']);
        $response = $controller->activateAndAcceptAction($request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(1, $response->headers->get('X-TeamInviteActivateAndAccept-Error-Code'));
        $this->assertEquals('No invite for token', $response->headers->get('X-TeamInviteActivateAndAccept-Error-Message'));
    }

}