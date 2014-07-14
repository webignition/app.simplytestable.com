<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\Access\TeamAccess;

use SimplyTestable\ApiBundle\Entity\User;

abstract class CreatedByMemberAccessedByDifferentMemberTest extends TeamAccessTest {

    /**
     * @var User
     */
    var $leader;


    /**
     * @var User
     */
    var $member1;

    /**
     * @var User
     */
    var $member2;

    public function preCreateJob() {
        $this->leader = $this->createAndActivateUser('leader@example.com');
        $this->member1 = $this->createAndActivateUser('user1@example.com');
        $this->member2 = $this->createAndActivateUser('user2@example.com');

        $team = $this->getTeamService()->create(
            'Foo',
            $this->leader
        );

        $this->getTeamMemberService()->add($team, $this->member1);
        $this->getTeamMemberService()->add($team, $this->member2);
    }


    protected function getJobOwner() {
        return $this->member1;
    }


    protected function getJobAccessor() {
        return $this->member2;
    }

}


