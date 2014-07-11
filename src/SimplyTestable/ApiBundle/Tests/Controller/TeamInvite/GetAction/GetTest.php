<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\TeamInvite\GetAction;

use SimplyTestable\ApiBundle\Tests\Controller\TeamInvite\ActionTest;

class GetTest extends ActionTest {

    public function testInviterIsNotTeamLeaderReturnsBadResponse() {
        $inviter = $this->createAndActivateUser('user1@example.com', 'password');
        $invitee = $this->createAndActivateUser('user2@example.com', 'password');

        $this->getUserService()->setUser($inviter);

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController()->$methodName($invitee->getEmail());

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(1, $response->headers->get('X-TeamInviteGet-Error-Code'));
        $this->assertEquals('Inviter is not a team leader', $response->headers->get('X-TeamInviteGet-Error-Message'));
    }


    public function testInviteeIsNotAUserReturnsBadResponse() {
        $inviter = $this->createAndActivateUser('user1@example.com', 'password');

        $this->getUserService()->setUser($inviter);

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController()->$methodName('user2@example.com');

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(9, $response->headers->get('X-TeamInviteGet-Error-Code'));
        $this->assertEquals('Invitee is not a user', $response->headers->get('X-TeamInviteGet-Error-Message'));
    }


    public function testInviteeIsTeamLeaderReturnsBadResponse() {
        $inviter = $this->createAndActivateUser('inviter@example.com', 'password');
        $invitee = $this->createAndActivateUser('invitee@example.com', 'password');

        $this->getUserService()->setUser($inviter);

        $this->getTeamService()->create(
            'Foo1',
            $inviter
        );

        $this->getTeamService()->create(
            'Foo2',
            $invitee
        );

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController()->$methodName($invitee->getEmail());

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(2, $response->headers->get('X-TeamInviteGet-Error-Code'));
        $this->assertEquals('Invitee is a team leader', $response->headers->get('X-TeamInviteGet-Error-Message'));
    }


    public function testInviteeIsAlreadyOnADifferentTeamReturnsBadResponse() {
        $leader = $this->createAndActivateUser('leader@example.com', 'password');
        $inviter = $this->createAndActivateUser('inviter@example.com', 'password');
        $invitee = $this->createAndActivateUser('invitee@example.com', 'password');

        $leaderTeam = $this->getTeamService()->create(
            'Foo1',
            $leader
        );

        $this->getTeamMemberService()->add($leaderTeam, $invitee);

        $this->getTeamService()->create(
            'Foo2',
            $inviter
        );

        $this->getUserService()->setUser($inviter);

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController()->$methodName($invitee->getEmail());

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(3, $response->headers->get('X-TeamInviteGet-Error-Code'));
        $this->assertEquals('Invitee is on a team', $response->headers->get('X-TeamInviteGet-Error-Message'));
    }


    public function testGetNewInviteReturnsInvite() {
        $inviter = $this->createAndActivateUser('inviter@example.com', 'password');
        $invitee = $this->createAndActivateUser('invitee@example.com', 'password');

        $this->getTeamService()->create(
            'Foo',
            $inviter
        );

        $this->getUserService()->setUser($inviter);

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController()->$methodName($invitee->getEmail());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('token', json_decode($response->getContent(), true));
        $this->assertNotNull(json_decode($response->getContent(), true)['token']);
    }


    public function testGetExistingInviteReturnsInvite() {
        $inviter = $this->createAndActivateUser('inviter@example.com', 'password');
        $invitee = $this->createAndActivateUser('invitee@example.com', 'password');

        $this->getTeamService()->create(
            'Foo',
            $inviter
        );

        $this->getUserService()->setUser($inviter);

        $methodName = $this->getActionNameFromRouter();

        $response1 = $this->getCurrentController()->$methodName($invitee->getEmail());
        $response2 = $this->getCurrentController()->$methodName($invitee->getEmail());

        $this->assertEquals(json_decode($response1->getContent(), true)['token'], json_decode($response2->getContent(), true)['token']);
    }


    public function testInvitePublicUserReturnsBadRequest() {
        $inviter = $this->createAndActivateUser('inviter@example.com', 'password');

        $this->getTeamService()->create(
            'Foo',
            $inviter
        );

        $this->getUserService()->setUser($inviter);

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController()->$methodName($this->getUserService()->getPublicUser()->getEmail());

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(10, $response->headers->get('X-TeamInviteGet-Error-Code'));
        $this->assertEquals('Special users cannot be invited', $response->headers->get('X-TeamInviteGet-Error-Message'));
    }


    public function testInviteAdminUserReturnsBadRequest() {
        $inviter = $this->createAndActivateUser('inviter@example.com', 'password');

        $this->getTeamService()->create(
            'Foo',
            $inviter
        );

        $this->getUserService()->setUser($inviter);

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController()->$methodName($this->getUserService()->getAdminUser()->getEmail());

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(10, $response->headers->get('X-TeamInviteGet-Error-Code'));
        $this->assertEquals('Special users cannot be invited', $response->headers->get('X-TeamInviteGet-Error-Message'));
    }


    /**
     *
     * @return array
     */
    protected function getRouteParameters() {
        return [
            'invitee_email' => 'user@example.com'
        ];
    }
    
}