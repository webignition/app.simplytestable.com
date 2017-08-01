<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\TeamMember\GetMembers;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\TeamMember\ServiceTest;

class GetMembersTest extends ServiceTest
{
    public function testGetMembers()
    {
        $userFactory = new UserFactory($this->container);

        $leader = $userFactory->createAndActivateUser('leader@example.com');
        $member1 = $userFactory->createAndActivateUser('member1@example.com');
        $member2 = $userFactory->createAndActivateUser('member2@example.com');

        $team = $this->getTeamService()->create('Foo', $leader);

        $member1 = $this->getTeamMemberService()->add($team, $member1);
        $member2 = $this->getTeamMemberService()->add($team, $member2);

        $members = $this->getTeamMemberService()->getMembers($team);

        $this->assertEquals(2, count($members));

        $this->assertEquals($member1->getId(), $members[0]->getId());
        $this->assertEquals($member2->getId(), $members[1]->getId());
    }
}
