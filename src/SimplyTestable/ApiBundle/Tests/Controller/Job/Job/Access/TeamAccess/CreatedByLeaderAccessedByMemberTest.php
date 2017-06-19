<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\Access\TeamAccess;

use SimplyTestable\ApiBundle\Entity\User;

abstract class CreatedByLeaderAccessedByMemberTest extends TeamAccessTest
{
    /**
     * @var User
     */
    private $leader;

    /**
     * @var User
     */
    private $member;

    public function preCreateJob()
    {
        $this->leader = $this->createAndActivateUser('leader@example.com');
        $this->member = $this->createAndActivateUser('user@example.com');

        $team = $this->getTeamService()->create(
            'Foo',
            $this->leader
        );

        $this->getTeamMemberService()->add($team, $this->member);
    }

    protected function getJobOwner()
    {
        return $this->leader;
    }

    protected function getJobAccessor()
    {
        return $this->member;
    }
}
