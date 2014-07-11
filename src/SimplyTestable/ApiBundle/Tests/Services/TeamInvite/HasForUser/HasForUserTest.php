<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Team\TeamInvite\GetForUser;

use SimplyTestable\ApiBundle\Tests\Services\TeamInvite\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\TeamInvite\Exception as TeamInviteServiceException;

class HasForUserTest extends ServiceTest {


    public function testReturnsNullIfNoInvite() {
        $user = $this->createAndActivateUser('user@example.com', 'password');
        $this->assertFalse($this->getTeamInviteService()->hasForUser($user));
    }


    public function testReturnsInvite() {
        $leader = $this->createAndActivateUser('leader@example.com', 'password');
        $user = $this->createAndActivateUser('user@example.com', 'password');

        $this->getTeamService()->create(
            'Foo1',
            $leader
        );

        $this->getTeamInviteService()->get(
            $leader,
            $user
        );

        $this->assertTrue($this->getTeamInviteService()->hasForUser($user));
    }

}
