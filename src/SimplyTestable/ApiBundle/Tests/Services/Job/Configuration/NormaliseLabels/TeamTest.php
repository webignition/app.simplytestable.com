<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\NormaliseLabels;

use SimplyTestable\ApiBundle\Entity\Team\Team;
use SimplyTestable\ApiBundle\Entity\User;

abstract class TeamTest extends ServiceTest {

    /**
     * @var User
     */
    protected $leader;


    /**
     * @var User
     */
    protected $user1;

    /**
     * @var User
     */
    protected $user2;


    /**
     * @var Team
     */
    protected $team;


    public function setUp() {
        parent::setUp();

        $this->leader = $this->createAndActivateUser('leader@example.com', 'password');
        $this->user1 = $this->createAndActivateUser('user1@example.com');
        $this->user2 = $this->createAndActivateUser('user2@example.com');

        $this->team = $this->getTeamService()->create(
            'Foo',
            $this->leader
        );
    }

}