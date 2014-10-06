<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\CancelAction;

class TeamMemberCancelJobStartedByDifferentTeamMember extends IsCancelledTest {

    private $leader;
    private $member1;
    private $member2;

    protected function preCall() {
        $this->getUserService()->setUser($this->getMember2());

        $team = $this->getTeamService()->create(
            'Foo',
            $this->getLeader()
        );

        $this->getTeamMemberService()->add($team, $this->getMember1());
        $this->getTeamMemberService()->add($team, $this->getMember2());
    }

    protected function getJob() {
        $job = $this->getJobService()->getById($this->createJobAndGetId(
            self::DEFAULT_CANONICAL_URL,
            $this->getMember1()->getEmail()
        ));

        return $job;
    }

    protected function getExpectedJobStartingState() {
        return $this->getJobService()->getStartingState();
    }

    private function getLeader() {
        if (is_null($this->leader)) {
            $this->leader = $this->createAndActivateUser('leader@example.com');
        }

        return $this->leader;
    }


    private function getMember1() {
        if (is_null($this->member1)) {
            $this->member1 = $this->createAndActivateUser('member1@example.com');
        }

        return $this->member1;
    }

    private function getMember2() {
        if (is_null($this->member2)) {
            $this->member2 = $this->createAndActivateUser('member2@example.com');
        }

        return $this->member2;
    }
    
}


