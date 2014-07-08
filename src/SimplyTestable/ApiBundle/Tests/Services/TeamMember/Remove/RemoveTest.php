<?php

namespace SimplyTestable\ApiBundle\Tests\Services\TeamMember\Add;

use SimplyTestable\ApiBundle\Tests\Services\TeamMember\ServiceTest;

class RemoveTest extends ServiceTest {

    public function testRemoveUserThatIsNotOnATeamReturnsTrue() {
        $this->assertTrue($this->getTeamMemberService()->remove($this->createAndActivateUser('user@example.com', 'password')));
    }


    public function testRemoveUserThatIsOnATeamReturnsTrue() {
        $leader = $this->createAndActivateUser('leader@example.com', 'password');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $user = $this->createAndActivateUser('user@example.com', 'password');

        $this->getTeamMemberService()->add($team, $user);

        $this->assertTrue($this->getTeamMemberService()->remove($user));
    }


    public function testRemoveUserThatIsOnATeamRemovesTheUser() {
        $leader = $this->createAndActivateUser('leader@example.com', 'password');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $user = $this->createAndActivateUser('user@example.com', 'password');

        $this->getTeamMemberService()->add($team, $user);

        $this->assertTrue($this->getTeamMemberService()->contains($team, $user));

        $this->getTeamMemberService()->remove($user);

        $this->assertFalse($this->getTeamMemberService()->contains($team, $user));
    }

}
