<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\Get;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

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
        $teamMemberService = $this->container->get('simplytestable.services.teammemberservice');

        $userFactory = new UserFactory($this->container);

        $leader = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $this->member = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'member@example.com',
        ]);

        $teamMemberService->add($this->getTeamService()->create(
            'Foo',
            $leader
        ), $this->member);

        $this->user = $userFactory->createAndActivateUser();
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
