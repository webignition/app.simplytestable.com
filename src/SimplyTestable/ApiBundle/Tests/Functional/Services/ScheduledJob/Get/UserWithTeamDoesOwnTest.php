<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\Get;

use SimplyTestable\ApiBundle\Entity\User;

class UserWithTeamDoesOwnTest extends IsRetrievedTest {

    /**
     * @var User
     */
    private $leader;


    /**
     * @var User
     */
    private $member;


    protected function setUpPreCreate() {
        $this->leader = $this->createAndActivateUser('leader@example.com', 'password');
        $this->member = $this->createAndActivateUser('member@example.com');

        $this->getTeamMemberService()->add($this->getTeamService()->create(
            'Foo',
            $this->leader
        ), $this->member);
    }

    protected function getJobConfigurationOwner()
    {
        return $this->leader;
    }

    protected function getServiceRequestUser()
    {
        return $this->member;
    }

}
