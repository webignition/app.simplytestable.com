<?php

namespace Tests\AppBundle\Functional\Entity\Team;

use Tests\AppBundle\Factory\UserFactory;
use Tests\AppBundle\Functional\AbstractBaseTestCase;
use AppBundle\Entity\Team\Team;

class TeamTest extends AbstractBaseTestCase
{
    public function testPersist()
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');

        $userFactory = new UserFactory(self::$container);

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