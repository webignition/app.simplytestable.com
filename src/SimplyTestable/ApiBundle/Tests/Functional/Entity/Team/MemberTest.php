<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\Task;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Team\Team;
use SimplyTestable\ApiBundle\Entity\Team\Member;

class MemberTest extends BaseSimplyTestableTestCase
{
    public function testPersist()
    {
        $userFactory = new UserFactory($this->container);

        $team = new Team();
        $team->setLeader($userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'team-leader@example.com',
        ]));
        $team->setName('Foo');

        $this->getManager()->persist($team);
        $this->getManager()->flush();

        $member = new Member();
        $member->setTeam($team);
        $member->setUser($userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'team-member@example.com',
        ]));

        $this->getManager()->persist($member);
        $this->getManager()->flush();
    }
}
