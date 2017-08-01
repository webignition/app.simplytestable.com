<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\Get;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

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
        $userFactory = new UserFactory($this->container);

        $this->leader = $userFactory->createAndActivateUser('leader@example.com');
        $this->member = $userFactory->createAndActivateUser('member@example.com');

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
