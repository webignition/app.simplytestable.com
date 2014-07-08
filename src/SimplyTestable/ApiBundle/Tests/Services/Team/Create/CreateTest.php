<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Team\Create;

use SimplyTestable\ApiBundle\Tests\Services\Team\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\Team\Exception as TeamServiceException;

class CreateTest extends ServiceTest {
    
    public function testEmptyNameThrowsTeamServiceException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Team\Exception',
            '',
            TeamServiceException::CODE_NAME_EMPTY
        );

        $this->getTeamService()->create(
            '',
            $this->createAndActivateUser('user@example.com', 'password')
        );
    }


    public function testTakenNameThrowsTeamServiceException() {
        $user = $this->createAndActivateUser('user@example.com', 'password');

        $this->getTeamService()->create(
            'Foo',
            $user
        );

        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Team\Exception',
            '',
            TeamServiceException::CODE_NAME_TAKEN
        );

        $this->getTeamService()->create(
            'Foo',
            $user
        );
    }


    public function testUserAlreadyLeadsTeamThrowsTeamServiceException() {
        $user = $this->createAndActivateUser('user@example.com', 'password');

        $this->getTeamService()->create(
            'Foo',
            $user
        );

        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Team\Exception',
            '',
            TeamServiceException::USER_ALREADY_LEADS_TEAM
        );

        $this->getTeamService()->create(
            'Bar',
            $user
        );
    }


    public function testUserAlreadyOnTeamThrowsTeamMemberServiceException() {
        $leader = $this->createAndActivateUser('leader@example.com', 'password');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $user = $this->createAndActivateUser('user@example.com', 'password');

        $this->getTeamMemberService()->setTeam($team);
        $this->getTeamMemberService()->add($user);

        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Team\Exception',
            '',
            TeamServiceException::USER_ALREADY_ON_TEAM
        );

        $this->getTeamService()->create(
            'Bar',
            $user
        );
    }

}
