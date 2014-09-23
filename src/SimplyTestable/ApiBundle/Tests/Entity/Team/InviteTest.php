<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\Task;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Team\Team;
use SimplyTestable\ApiBundle\Entity\Team\Invite;

class InviteTest extends BaseSimplyTestableTestCase {

    const TEAM_NAME = 'Foo';
    const TOKEN = 'token';

    public function testPersist() {
        $team = new Team();
        $team->setLeader($this->createAndActivateUser('team-leader@example.com', 'password'));
        $team->setName('Foo');

        $this->getManager()->persist($team);
        $this->getManager()->flush();

        $invite = new Invite();
        $invite->setTeam($team);
        $invite->setUser($this->createAndActivateUser('team-member@example.com', 'password'));
        $invite->setToken(self::TOKEN);

        $this->getManager()->persist($invite);
        $this->getManager()->flush();

        $inviteId = $invite->getId();

        $retrievedInvite = $this->getTeamInviteService()->getEntityRepository()->find($inviteId);

        $this->assertEquals($inviteId, $retrievedInvite->getId());
        $this->assertEquals(self::TOKEN, $retrievedInvite->getToken());
    }

}
