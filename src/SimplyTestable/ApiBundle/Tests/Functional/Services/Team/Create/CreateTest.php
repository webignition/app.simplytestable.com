<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Team\Create;

use SimplyTestable\ApiBundle\Tests\Functional\Services\Team\ServiceTest;
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
        $this->getTeamService()->create(
            'Foo',
            $this->createAndActivateUser('user1@example.com', 'password')
        );

        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Team\Exception',
            '',
            TeamServiceException::CODE_NAME_TAKEN
        );

        $this->getTeamService()->create(
            'Foo',
            $this->createAndActivateUser('user2@example.com', 'password')
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

        $this->getTeamMemberService()->add($team, $user);

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
