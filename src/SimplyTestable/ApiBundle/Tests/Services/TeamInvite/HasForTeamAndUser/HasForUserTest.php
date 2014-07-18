<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Team\TeamInvite\HasForTeamAndUser;

use SimplyTestable\ApiBundle\Tests\Services\TeamInvite\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\TeamInvite\Exception as TeamInviteServiceException;

class HasForTeamAndUserTest extends ServiceTest {


    public function testReturnsNullIfNoInvite() {
        $leader = $this->createAndActivateUser('leader@example.com');
        $team = $this->getTeamService()->create(
            'Foo1',
            $leader
        );

        $user = $this->createAndActivateUser('user@example.com', 'password');
        $this->assertFalse($this->getTeamInviteService()->hasForTeamAndUser($team, $user));
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

        $this->assertTrue($this->getTeamInviteService()->hasForTeamAndUser($team, $user));
    }

}
