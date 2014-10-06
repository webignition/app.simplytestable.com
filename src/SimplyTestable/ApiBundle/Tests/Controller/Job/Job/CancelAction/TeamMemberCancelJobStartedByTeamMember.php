<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\CancelAction;

class TeamMemberCancelJobStartedByTeamMember extends IsCancelledTest {

    private $leader;
    private $member;

    protected function preCall() {
        $this->getUserService()->setUser($this->getMember());

        $team = $this->getTeamService()->create(
            'Foo',
            $this->getLeader()
        );

        $this->getTeamMemberService()->add($team, $this->getMember());
    }

    protected function getJob() {
        $job = $this->getJobService()->getById($this->createJobAndGetId(
            self::DEFAULT_CANONICAL_URL,
            $this->getLeader()->getEmail()
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


    private function getMember() {
        if (is_null($this->member)) {
            $this->member = $this->createAndActivateUser('member@example.com');
        }

        return $this->member;
    }
    
}


