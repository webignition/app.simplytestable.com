<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Team\TeamInvite\GetForTeamAndUser;

use SimplyTestable\ApiBundle\Tests\Services\TeamInvite\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\TeamInvite\Exception as TeamInviteServiceException;

class GetForTeamAndUserTest extends ServiceTest {


    public function testReturnsNullIfNoInvite() {
        $leader = $this->createAndActivateUser('leader@example.com');
        $team = $this->getTeamService()->create(
            'Foo1',
            $leader
        );

        $user = $this->createAndActivateUser('user@example.com', 'password');
        $this->assertNull($this->getTeamInviteService()->getForTeamAndUser($team, $user));
    }


    public function testReturnsInvite() {
        $leader = $this->createAndActivateUser('leader@example.com', 'password');
        $user = $this->createAndActivateUser('user@example.com', 'password');

        $team = $this->getTeamService()->create(
            'Foo1',
            $leader
        );

        $this->getTeamInviteService()->get(
            $leader,
            $user
        );

        $invite = $this->getTeamInviteService()->getForTeamAndUser($team, $user);

        $this->assertNotNull($invite->getId());
        $this->assertEquals($team->getId(), $invite->getTeam()->getId());
        $this->assertEquals($user->getId(), $invite->getUser()->getId());
    }

}
