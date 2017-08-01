<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\Get;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class UserWithoutTeamDoesNotOwnTest extends IsNotRetrievedTest {

    /**
     * @var User
     */
    private $user1;


    /**
     * @var User
     */
    private $user2;


    protected function setUpPreCreate() {
        $userFactory = new UserFactory($this->container);

        $this->user1 = $userFactory->createAndActivateUser('user1@example.com');
        $this->user2 = $userFactory->createAndActivateUser('user2@example.com');
    }

    protected function getJobConfigurationOwner()
    {
        return $this->user1;
    }

    protected function getServiceRequestUser()
    {
        return $this->user2;
    }
}

