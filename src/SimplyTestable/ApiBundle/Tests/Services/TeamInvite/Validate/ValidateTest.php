<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Team\TeamInvite\Validate;

use SimplyTestable\ApiBundle\Tests\Services\TeamInvite\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\TeamInvite\Exception as TeamInviteServiceException;

class ValidateTest extends ServiceTest {

    public function testInvalidInviteReturnsFalse() {
        $leader = $this->createAndActivateUser('leader@example.com', 'password');
        $user1 = $this->createAndActivateUser('user1@example.com', 'password');
        $user2 = $this->createAndActivateUser('user2@example.com', 'password');

        $team = $this->getTeamService()->create(
            'Foo1',
            $leader
        );

        $invite1 = $this->getTeamInviteService()->get(
            $leader,
            $user1
        );

        $invite2 = $this->getTeamInviteService()->get(
            $leader,
            $user2
        );

        $this->assertFalse($this->getTeamInviteService()->validate($user1, $invite2));
    }


    public function testValidInviteReturnsTrue() {
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

        $this->assertTrue($this->getTeamInviteService()->validate($user, $invite));
    }


//    public function testGetNewInviteReturnsInvite() {
//        $leader = $this->createAndActivateUser('leader@example.com', 'password');
//        $user = $this->createAndActivateUser('user@example.com', 'password');
//
//        $team = $this->getTeamService()->create(
//            'Foo1',
//            $leader
//        );
//
//        $invite = $this->getTeamInviteService()->get(
//            $leader,
//            $user
//        );
//
//        $this->assertNotNull($invite->getId());
//        $this->assertEquals($team->getId(), $invite->getTeam()->getId());
//        $this->assertEquals($user->getId(), $invite->getUser()->getId());
//        $this->assertNotNull($invite->getToken());
//    }
//
//    public function testGetExistingInviteReturnsExistingInvite() {
//        $leader = $this->createAndActivateUser('leader@example.com', 'password');
//        $user = $this->createAndActivateUser('user@example.com', 'password');
//
//        $this->getTeamService()->create(
//            'Foo1',
//            $leader
//        );
//
//        $invite1 = $this->getTeamInviteService()->get(
//            $leader,
//            $user
//        );
//
//        $invite2 = $this->getTeamInviteService()->get(
//            $leader,
//            $user
//        );
//
//        $this->assertEquals($invite1, $invite2);
//    }

}
