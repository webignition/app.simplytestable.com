<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Team\TeamInvite\GetForUser;

use SimplyTestable\ApiBundle\Tests\Services\TeamInvite\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\TeamInvite\Exception as TeamInviteServiceException;

class GetForUserTest extends ServiceTest {


    public function testReturnsNullIfNoInvite() {
        $user = $this->createAndActivateUser('user@example.com', 'password');
        $this->assertNull($this->getTeamInviteService()->getForUser($user));
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

        $invite = $this->getTeamInviteService()->getForUser($user);

        $this->assertNotNull($invite->getId());
        $this->assertEquals($team->getId(), $invite->getTeam()->getId());
        $this->assertEquals($user->getId(), $invite->getUser()->getId());
        $this->assertNotNull($invite->getToken());
    }

}
