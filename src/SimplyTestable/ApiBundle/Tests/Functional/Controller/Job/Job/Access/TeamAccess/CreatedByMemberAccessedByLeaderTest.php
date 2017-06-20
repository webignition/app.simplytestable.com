<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\Access\TeamAccess;

use SimplyTestable\ApiBundle\Entity\User;

abstract class CreatedByMemberAccessedByLeaderTest extends TeamAccessTest {

    /**
     * @var User
     */
    var $leader;


    /**
     * @var User
     */
    var $member;

    public function preCreateJob() {
        $this->leader = $this->createAndActivateUser('leader@example.com');
        $this->member = $this->createAndActivateUser('user@example.com');

        $team = $this->getTeamService()->create(
            'Foo',
            $this->leader
        );

        $this->getTeamMemberService()->add($team, $this->member);
    }


    protected function getJobOwner() {
        return $this->member;
    }


    protected function getJobAccessor() {
        return $this->leader;
    }

}


