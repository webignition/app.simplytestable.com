<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\Task;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Team\Team;

class TeamTest extends BaseSimplyTestableTestCase {

    public function testPersist() {
        $team = new Team();
        $team->setLeader($this->createAndActivateUser('user@example.com', 'password'));
        $team->setName('Foo');

        $this->getManager()->persist($team);
        $this->getManager()->flush();
    }

}