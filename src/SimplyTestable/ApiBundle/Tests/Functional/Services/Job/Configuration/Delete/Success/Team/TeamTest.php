<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Delete\Success\Team;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Delete\Success\SuccessTest;

abstract class TeamTest extends SuccessTest {

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


    public function preCreateJobConfigurations() {
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


    protected function getCurrentUser() {
        return $this->getUserService()->getPublicUser();
    }

}