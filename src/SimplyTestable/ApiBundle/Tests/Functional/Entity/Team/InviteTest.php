<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\Task;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Team\Team;
use SimplyTestable\ApiBundle\Entity\Team\Invite;

class InviteTest extends BaseSimplyTestableTestCase
{
    const TEAM_NAME = 'Foo';
    const TOKEN = 'token';

    public function testPersist()
    {
        $userFactory = new UserFactory($this->container);

        $team = new Team();
        $team->setLeader($userFactory->createAndActivateUser('team-leader@example.com'));
        $team->setName('Foo');

        $this->getManager()->persist($team);
        $this->getManager()->flush();

        $invite = new Invite();
        $invite->setTeam($team);
        $invite->setUser($userFactory->createAndActivateUser('team-member@example.com'));
        $invite->setToken(self::TOKEN);

        $this->getManager()->persist($invite);
        $this->getManager()->flush();

        $inviteId = $invite->getId();

        $retrievedInvite = $this->getTeamInviteService()->getEntityRepository()->find($inviteId);

        $this->assertEquals($inviteId, $retrievedInvite->getId());
        $this->assertEquals(self::TOKEN, $retrievedInvite->getToken());
    }

}
