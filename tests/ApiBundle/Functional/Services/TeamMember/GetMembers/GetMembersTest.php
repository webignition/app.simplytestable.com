<?php

namespace Tests\ApiBundle\Functional\Services\TeamMember\GetMembers;

use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\Services\TeamMember\ServiceTest;

class GetMembersTest extends ServiceTest
{
    public function testGetMembers()
    {
        $teamMemberService = $this->container->get('simplytestable.services.teammemberservice');
        $teamService = $this->container->get('simplytestable.services.teamservice');

        $userFactory = new UserFactory($this->container);

        $leader = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);

        $member1 = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'member1@example.com',
        ]);
        $member2 = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'member2@example.com',
        ]);

        $team = $teamService->create('Foo', $leader);

        $member1 = $teamMemberService->add($team, $member1);
        $member2 = $teamMemberService->add($team, $member2);

        $members = $teamMemberService->getMembers($team);

        $this->assertEquals(2, count($members));

        $this->assertEquals($member1->getId(), $members[0]->getId());
        $this->assertEquals($member2->getId(), $members[1]->getId());
    }
}
