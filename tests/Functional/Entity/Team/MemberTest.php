<?php

namespace App\Tests\Functional\Entity\Team;

use App\Repository\TeamMemberRepository;
use App\Tests\Services\UserFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Entity\Team\Team;
use App\Entity\Team\Member;
use Doctrine\ORM\EntityManagerInterface;

class MemberTest extends AbstractBaseTestCase
{
    public function testPersist()
    {
        $entityManager = self::$container->get(EntityManagerInterface::class);
        $memberRepository = self::$container->get(TeamMemberRepository::class);

        $userFactory = self::$container->get(UserFactory::class);

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

        $retrievedMember = $memberRepository->find($memberId);

        $this->assertSame($member->getId(), $retrievedMember->getId());
    }
}
