<?php

namespace Tests\ApiBundle\Functional\Entity\Team;

use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Entity\Team\Team;

class TeamTest extends AbstractBaseTestCase
{
    public function testPersist()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $userFactory = new UserFactory($this->container);

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
