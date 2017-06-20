<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Team\TeamInvite\GetForTeam;

use SimplyTestable\ApiBundle\Tests\Functional\Services\TeamInvite\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\TeamInvite\Exception as TeamInviteServiceException;

class GetForTeamTest extends ServiceTest {

    public function testTeamWithNoInvitesReturnsEmptyCollection() {
        $leader = $this->createAndActivateUser('leader@example.com', 'password');

        $team = $this->getTeamService()->create(
            'Foo1',
            $leader
        );

        $this->assertEquals([], $this->getTeamInviteService()->getForTeam($team));
    }


    public function testTeamWithInvitesReturnsInvites() {
        $leader = $this->createAndActivateUser('leader@example.com', 'password');
        $user = $this->createAndActivateUser('user@example.com', 'password');

        $team = $this->getTeamService()->create(
            'Foo1',
            $leader
        );

        $invite = $this->getTeamInviteService()->get($leader, $user);

        $this->assertEquals([$invite], $this->getTeamInviteService()->getForTeam($team));
    }

}
