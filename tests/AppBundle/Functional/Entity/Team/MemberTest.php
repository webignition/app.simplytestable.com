<?php

namespace Tests\AppBundle\Functional\Entity\Team;

use Tests\AppBundle\Factory\UserFactory;
use Tests\AppBundle\Functional\AbstractBaseTestCase;
use AppBundle\Entity\Team\Team;
use AppBundle\Entity\Team\Member;

class MemberTest extends AbstractBaseTestCase
{
    public function testPersist()
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');

        $userFactory = new UserFactory(self::$container);

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
