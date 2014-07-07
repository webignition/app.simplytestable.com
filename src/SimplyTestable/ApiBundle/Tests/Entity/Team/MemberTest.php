<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\Task;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Team\Team;
use SimplyTestable\ApiBundle\Entity\Team\Member;

class MemberTest extends BaseSimplyTestableTestCase {

    public function testPersist() {
        $team = new Team();
        $team->setLeader($this->createAndActivateUser('team-leader@example.com', 'password'));
        $team->setName('Foo');

        $this->getEntityManager()->persist($team);
        $this->getEntityManager()->flush();

        $member = new Member();
        $member->setTeam($team);
        $member->setUser($this->createAndActivateUser('team-member@example.com', 'password'));

        $this->getEntityManager()->persist($member);
        $this->getEntityManager()->flush();
    }

}
