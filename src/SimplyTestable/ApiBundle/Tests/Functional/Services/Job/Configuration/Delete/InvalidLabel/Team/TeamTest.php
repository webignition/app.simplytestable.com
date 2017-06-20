<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Delete\InvalidLabel\Team;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Delete\InvalidLabel\InvalidLabelTest;

abstract class TeamTest extends InvalidLabelTest {

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


    public function setUp() {
        parent::setUp();

        $this->leader = $this->createAndActivateUser('leader@example.com', 'password');
        $this->member1 = $this->createAndActivateUser('user1@example.com');
        $this->member2 = $this->createAndActivateUser('user2@example.com');

        $team = $this->getTeamService()->create(
            'Foo',
            $this->leader
        );

        $this->getTeamMemberService()->add($team, $this->member1);
        $this->getTeamMemberService()->add($team, $this->member2);
    }

}