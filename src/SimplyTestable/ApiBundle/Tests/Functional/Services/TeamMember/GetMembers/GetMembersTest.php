<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\TeamMember\GetMembers;

use SimplyTestable\ApiBundle\Tests\Functional\Services\TeamMember\ServiceTest;

class GetMembersTest extends ServiceTest {

    public function testGetMembers() {
        $leader = $this->createAndActivateUser('leader@example.com', 'password');
        $member1 = $this->createAndActivateUser('member1@example.com', 'password');
        $member2 = $this->createAndActivateUser('member2@example.com', 'password');

        $team = $this->getTeamService()->create('Foo', $leader);

        $member1 = $this->getTeamMemberService()->add($team, $member1);
        $member2 = $this->getTeamMemberService()->add($team, $member2);

        $members = $this->getTeamMemberService()->getMembers($team);

        $this->assertEquals(2, count($members));

        $this->assertEquals($member1->getId(), $members[0]->getId());
        $this->assertEquals($member2->getId(), $members[1]->getId());
    }

}
