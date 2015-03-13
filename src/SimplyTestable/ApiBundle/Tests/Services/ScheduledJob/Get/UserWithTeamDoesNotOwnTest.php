<?php

namespace SimplyTestable\ApiBundle\Tests\Services\ScheduledJob\Get;

use SimplyTestable\ApiBundle\Entity\User;

class UserWithTeamDoesNotOwnTest extends IsNotRetrievedTest {

    /**
     * @var User
     */
    private $member;


    /**
     * @var User
     */
    private $user;


    protected function setUpPreCreate() {
        $leader = $this->createAndActivateUser('leader@example.com', 'password');
        $this->member = $this->createAndActivateUser('member@example.com');

        $this->getTeamMemberService()->add($this->getTeamService()->create(
            'Foo',
            $leader
        ), $this->member);

        $this->user = $this->createAndActivateUser('user@example.com');
    }

    protected function getJobConfigurationOwner()
    {
        return $this->user;
    }

    protected function getServiceRequestUser()
    {
        return $this->member;
    }

}
