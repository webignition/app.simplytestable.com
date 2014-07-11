<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\TeamInvite\AcceptAction;

use SimplyTestable\ApiBundle\Tests\Controller\TeamInvite\ActionTest;

class AcceptTest extends ActionTest {

    public function testUserHasNoInviteReturnsBadResponse() {
        $user = $this->createAndActivateUser('user@example.com');
        $this->getUserService()->setUser($user);

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController()->$methodName();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(1, $response->headers->get('X-TeamInviteAccept-Error-Code'));
        $this->assertEquals('User has no invite', $response->headers->get('X-TeamInviteAccept-Error-Message'));
    }


    public function testUserWithInvalidTokenReturnsBadResponse() {
        $inviter = $this->createAndActivateUser('inviter@example.com', 'password');
        $invitee = $this->createAndActivateUser('invitee@example.com', 'password');

        $this->getTeamService()->create(
            'Foo',
            $inviter
        );

        $this->getTeamInviteService()->get($inviter, $invitee);

        $this->getUserService()->setUser($invitee);

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController([
            'token' => 'foobar'
        ])->$methodName();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(2, $response->headers->get('X-TeamInviteAccept-Error-Code'));
        $this->assertEquals('Invalid token', $response->headers->get('X-TeamInviteAccept-Error-Message'));
    }


    public function testInviteIsAccepted() {
        $inviter = $this->createAndActivateUser('inviter@example.com', 'password');
        $invitee = $this->createAndActivateUser('invitee@example.com', 'password');

        $this->getTeamService()->create(
            'Foo',
            $inviter
        );

        $invite = $this->getTeamInviteService()->get($inviter, $invitee);

        $this->getUserService()->setUser($invitee);

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController([
            'token' => $invite->getToken()
        ])->$methodName();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($invite->getId());
    }
    
}