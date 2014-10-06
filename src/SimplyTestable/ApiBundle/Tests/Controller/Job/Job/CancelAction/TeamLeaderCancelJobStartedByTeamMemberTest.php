<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\CancelAction;

class TeamLeaderCancelJobStartedByTeamMemberTest extends IsCancelledTest {

    private $leader;
    private $member;

    protected function preCall() {
        $this->getUserService()->setUser($this->getLeader());

        $team = $this->getTeamService()->create(
            'Foo',
            $this->getLeader()
        );

        $this->getTeamMemberService()->add($team, $this->getMember());
    }

    protected function getJob() {
        $job = $this->getJobService()->getById($this->createJobAndGetId(
            self::DEFAULT_CANONICAL_URL,
            $this->getMember()->getEmail()
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


