<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\CancelAction;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class TeamMemberCancelJobStartedByDifferentTeamMember extends IsCancelledTest
{
    /**
     * @var User
     */
    private $leader;

    /**
     * @var User
     */
    private $member1;

    /**
     * @var User
     */
    private $member2;

    protected function preCall()
    {
        $this->getUserService()->setUser($this->getMember2());

        $team = $this->getTeamService()->create(
            'Foo',
            $this->getLeader()
        );

        $this->getTeamMemberService()->add($team, $this->getMember1());
        $this->getTeamMemberService()->add($team, $this->getMember2());
    }

    protected function getJob()
    {
        return $this->createJobFactory()->create([
            JobFactory::KEY_USER => $this->getMember1(),
        ]);
    }

    protected function getExpectedJobStartingState()
    {
        return $this->getJobService()->getStartingState();
    }

    private function getLeader()
    {
        if (is_null($this->leader)) {
            $this->leader = $this->createAndActivateUser('leader@example.com');
        }

        return $this->leader;
    }

    private function getMember1()
    {
        if (is_null($this->member1)) {
            $this->member1 = $this->createAndActivateUser('member1@example.com');
        }

        return $this->member1;
    }

    private function getMember2()
    {
        if (is_null($this->member2)) {
            $this->member2 = $this->createAndActivateUser('member2@example.com');
        }

        return $this->member2;
    }
}
