<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\Task;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Team;

class TeamTest extends BaseSimplyTestableTestCase {

    public function testPersist() {
        $team = new Team();
        $team->setLeader($this->createAndActivateUser('user@example.com', 'password'));
        $team->setName('Foo');

        $this->getEntityManager()->persist($team);
        $this->getEntityManager()->flush();
    }

}