<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Update\EmptyNewLabel\Team;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Update\EmptyNewLabel\EmptyNewLabelTest;

abstract class TeamTest extends EmptyNewLabelTest {

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