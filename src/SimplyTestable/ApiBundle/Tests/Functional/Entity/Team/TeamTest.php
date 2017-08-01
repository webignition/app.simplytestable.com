<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\Task;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Team\Team;

class TeamTest extends BaseSimplyTestableTestCase
{
    public function testPersist()
    {
        $userFactory = new UserFactory($this->container);

        $team = new Team();
        $team->setLeader($userFactory->createAndActivateUser('user@example.com'));
        $team->setName('Foo');

        $this->getManager()->persist($team);
        $this->getManager()->flush();
    }
}
