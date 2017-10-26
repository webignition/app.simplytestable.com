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
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $userFactory = new UserFactory($this->container);

        $team = new Team();
        $team->setLeader($userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'team-leader@example.com',
        ]));
        $team->setName('Foo');

        $entityManager->persist($team);
        $entityManager->flush();

        $member = new Member();
        $member->setTeam($team);
        $member->setUser($userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'team-member@example.com',
        ]));

        $entityManager->persist($member);
        $entityManager->flush();
    }
}
