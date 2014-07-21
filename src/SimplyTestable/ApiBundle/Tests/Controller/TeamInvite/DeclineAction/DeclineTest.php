<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\TeamInvite\DeclineAction;

use SimplyTestable\ApiBundle\Tests\Controller\TeamInvite\ActionTest;

class DeclineTest extends ActionTest {

    public function testUserDeclinesForNonexistentTeamReturnsOk() {
        $user = $this->createAndActivateUser('user@example.com');
        $this->getUserService()->setUser($user);

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController()->$methodName();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($this->getTeamInviteService()->hasAnyForUser($user));
        $this->assertFalse($this->getTeamMemberService()->belongsToTeam($user));
    }


    public function testUserHasNoInviteReturnsOk() {
        $leader = $this->createAndActivateUser('leader@example.com');
        $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $user = $this->createAndActivateUser('user@example.com');
        $this->getUserService()->setUser($user);

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController([
            'team' => 'Foo'
        ])->$methodName();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($this->getTeamInviteService()->hasAnyForUser($user));
        $this->assertFalse($this->getTeamMemberService()->belongsToTeam($user));
    }


    public function testInviteIsDeclined() {
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
            'team' => 'Foo'
        ])->$methodName();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($this->getTeamInviteService()->hasAnyForUser($invitee));
        $this->assertFalse($this->getTeamMemberService()->belongsToTeam($invitee));
    }


    protected function getRequestPostData() {
        return [
            'team' => 'Foo'
        ];
    }
    
}