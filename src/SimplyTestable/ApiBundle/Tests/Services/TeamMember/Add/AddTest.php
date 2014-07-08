<?php

namespace SimplyTestable\ApiBundle\Tests\Services\TeamMember\Add;

use SimplyTestable\ApiBundle\Tests\Services\TeamMember\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\TeamMember\Exception as TeamMemberServiceException;

class CreateTest extends ServiceTest {

    public function testUserAlreadyOnTeamThrowsTeamMemberServiceException() {
        $leader = $this->createAndActivateUser('leader@example.com', 'password');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $user = $this->createAndActivateUser('user@example.com', 'password');

        $this->getTeamMemberService()->add($team, $user);

        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\TeamMember\Exception',
            '',
            TeamMemberServiceException::USER_ALREADY_ON_TEAM
        );

        $this->getTeamMemberService()->add($team, $user);
    }


    public function testUserNotAlreadyOnTeamIsAdded() {
        $leader = $this->createAndActivateUser('leader@example.com', 'password');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $user = $this->createAndActivateUser('user@example.com', 'password');

        $member = $this->getTeamMemberService()->add($team, $user);

        $this->assertInstanceOf('SimplyTestable\ApiBundle\Entity\Team\Member', $member);
        $this->assertEquals($team->getId(), $member->getTeam()->getId());
        $this->assertEquals($user->getId(), $member->getUser()->getId());
    }

}
