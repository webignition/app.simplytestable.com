<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\CancelAction;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class TeamMemberCancelJobStartedByTeamMember extends IsCancelledTest
{
    /**
     * @var User
     */
    private $leader;

    /**
     * @var User
     */
    private $member;

    protected function preCall()
    {
        $this->getUserService()->setUser($this->getMember());

        $team = $this->getTeamService()->create(
            'Foo',
            $this->getLeader()
        );

        $this->getTeamMemberService()->add($team, $this->getMember());
    }

    protected function getJob()
    {
        return $this->createJobFactory()->create([
            JobFactory::KEY_USER => $this->getLeader(),
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

    private function getMember()
    {
        if (is_null($this->member)) {
            $this->member = $this->createAndActivateUser('member@example.com');
        }

        return $this->member;
    }
}
