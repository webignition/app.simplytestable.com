<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Team\TeamInvite\Get;

use SimplyTestable\ApiBundle\Tests\Functional\Services\TeamInvite\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\TeamInvite\Exception as TeamInviteServiceException;

class GetTest extends ServiceTest {

    public function testInviterIsNotTeamLeaderThrowsTeamInviteServiceException() {
        $user1 = $this->createAndActivateUser('user1@example.com', 'password');
        $user2 = $this->createAndActivateUser('user2@example.com', 'password');

        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\TeamInvite\Exception',
            '',
            TeamInviteServiceException::INVITER_IS_NOT_A_LEADER
        );

        $this->getTeamInviteService()->get(
            $user1,
            $user2
        );
    }

    public function testInviteeHasTeamThrowsTeamInviteServiceException() {
        $leader1 = $this->createAndActivateUser('leader1@example.com', 'password');
        $leader2 = $this->createAndActivateUser('leader2@example.com', 'password');

        $this->getTeamService()->create(
            'Foo1',
            $leader1
        );

        $this->getTeamService()->create(
            'Foo2',
            $leader2
        );

        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\TeamInvite\Exception',
            '',
            TeamInviteServiceException::INVITEE_IS_A_LEADER
        );

        $this->getTeamInviteService()->get(
            $leader1,
            $leader2
        );
    }

    public function testInviteeIsAlreadyInDifferentTeamThrowsTeamInviteServiceException() {
        $leader1 = $this->createAndActivateUser('leader1@example.com', 'password');
        $leader2 = $this->createAndActivateUser('leader2@example.com', 'password');
        $user = $this->createAndActivateUser('user@example.com', 'password');

        $team1 = $this->getTeamService()->create(
            'Foo1',
            $leader1
        );

        $this->getTeamService()->create(
            'Foo2',
            $leader2
        );

        $this->getTeamMemberService()->add($team1, $user);

        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\TeamInvite\Exception',
            '',
            TeamInviteServiceException::INVITEE_IS_ON_A_TEAM
        );

        $this->getTeamInviteService()->get(
            $leader2,
            $user
        );
    }

    public function testGetNewInviteReturnsInvite() {
        $leader = $this->createAndActivateUser('leader@example.com', 'password');
        $user = $this->createAndActivateUser('user@example.com', 'password');

        $team = $this->getTeamService()->create(
            'Foo1',
            $leader
        );

        $invite = $this->getTeamInviteService()->get(
            $leader,
            $user
        );

        $this->assertNotNull($invite->getId());
        $this->assertEquals($team->getId(), $invite->getTeam()->getId());
        $this->assertEquals($user->getId(), $invite->getUser()->getId());
    }

    public function testGetExistingInviteReturnsExistingInvite() {
        $leader = $this->createAndActivateUser('leader@example.com', 'password');
        $user = $this->createAndActivateUser('user@example.com', 'password');

        $this->getTeamService()->create(
            'Foo1',
            $leader
        );

        $invite1 = $this->getTeamInviteService()->get(
            $leader,
            $user
        );

        $invite2 = $this->getTeamInviteService()->get(
            $leader,
            $user
        );

        $this->assertEquals($invite1, $invite2);
    }

}
