<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Delete\InvalidLabel\Team;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Delete\InvalidLabel\InvalidLabelTest;

abstract class TeamTest extends InvalidLabelTest
{
    /**
     * @var User
     */
    protected $leader;


    /**
     * @var User
     */
    protected $member1;

    /**
     * @var User
     */
    protected $member2;


    protected function setUp()
    {
        parent::setUp();

        $userFactory = new UserFactory($this->container);

        $this->leader = $userFactory->createAndActivateUser('leader@example.com');
        $this->member1 = $userFactory->createAndActivateUser('user1@example.com');
        $this->member2 = $userFactory->createAndActivateUser('user2@example.com');

        $team = $this->getTeamService()->create(
            'Foo',
            $this->leader
        );

        $this->getTeamMemberService()->add($team, $this->member1);
        $this->getTeamMemberService()->add($team, $this->member2);
    }
}
