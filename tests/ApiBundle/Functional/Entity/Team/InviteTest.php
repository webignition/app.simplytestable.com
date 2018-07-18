<?php

namespace Tests\ApiBundle\Functional\Entity\Team;

use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Entity\Team\Team;
use SimplyTestable\ApiBundle\Entity\Team\Invite;

class InviteTest extends AbstractBaseTestCase
{
    const TEAM_NAME = 'Foo';
    const TOKEN = 'token';

    public function testPersist()
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $teamInviteRepository = $entityManager->getRepository(Invite::class);

        $userFactory = new UserFactory(self::$container);

        $team = new Team();
        $team->setLeader($userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'team-leader@example.com',
        ]));
        $team->setName('Foo');

        $entityManager->persist($team);
        $entityManager->flush();

        $invite = new Invite();
        $invite->setTeam($team);
        $invite->setUser($userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'team-member@example.com',
        ]));
        $invite->setToken(self::TOKEN);

        $entityManager->persist($invite);
        $entityManager->flush();

        $inviteId = $invite->getId();

        $retrievedInvite = $teamInviteRepository->find($inviteId);

        $this->assertEquals($inviteId, $retrievedInvite->getId());
        $this->assertEquals(self::TOKEN, $retrievedInvite->getToken());
    }

}
