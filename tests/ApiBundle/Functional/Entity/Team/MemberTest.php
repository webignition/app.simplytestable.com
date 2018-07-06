<?php

namespace Tests\ApiBundle\Functional\Entity\Team;

use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Entity\Team\Team;
use SimplyTestable\ApiBundle\Entity\Team\Member;

class MemberTest extends AbstractBaseTestCase
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

        $memberId = $member->getId();

        $entityManager->clear();

        $memberRepository = $entityManager->getRepository(Member::class);

        $retrievedMember = $memberRepository->find($memberId);

        $this->assertSame($member->getId(), $retrievedMember->getId());
    }
}
