<?php

namespace App\Tests\Functional\Entity\Team;

use App\Tests\Services\UserFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Entity\Team\Team;

class TeamTest extends AbstractBaseTestCase
{
    public function testPersist()
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');

        $userFactory = self::$container->get(UserFactory::class);

        $team = new Team();
        $team->setLeader($userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'user@example.com',
        ]));
        $team->setName('Foo');

        $entityManager->persist($team);
        $entityManager->flush();

        $teamId = $team->getId();

        $entityManager->clear();

        $memberRepository = $entityManager->getRepository(Team::class);

        $retrievedTeam = $memberRepository->find($teamId);

        $this->assertSame($team->getId(), $retrievedTeam->getId());
    }
}
