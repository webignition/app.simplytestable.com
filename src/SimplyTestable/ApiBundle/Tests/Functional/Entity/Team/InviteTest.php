<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\Task;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Entity\Team\Team;
use SimplyTestable\ApiBundle\Entity\Team\Invite;

class InviteTest extends AbstractBaseTestCase
{
    const TEAM_NAME = 'Foo';
    const TOKEN = 'token';

    public function testPersist()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');

        $userFactory = new UserFactory($this->container);

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

        $retrievedInvite = $teamInviteService->getEntityRepository()->find($inviteId);

        $this->assertEquals($inviteId, $retrievedInvite->getId());
        $this->assertEquals(self::TOKEN, $retrievedInvite->getToken());
    }

}
