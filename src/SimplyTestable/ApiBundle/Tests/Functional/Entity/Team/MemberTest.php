<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\Task;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Team\Team;
use SimplyTestable\ApiBundle\Entity\Team\Member;

class MemberTest extends BaseSimplyTestableTestCase {

    public function testPersist() {
        $team = new Team();
        $team->setLeader($this->createAndActivateUser('team-leader@example.com', 'password'));
        $team->setName('Foo');

        $this->getManager()->persist($team);
        $this->getManager()->flush();

        $member = new Member();
        $member->setTeam($team);
        $member->setUser($this->createAndActivateUser('team-member@example.com', 'password'));

        $this->getManager()->persist($member);
        $this->getManager()->flush();
    }

}
