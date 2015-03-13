<?php

namespace SimplyTestable\ApiBundle\Tests\Services\ScheduledJob\Get;

use SimplyTestable\ApiBundle\Entity\User;

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
        $this->user1 = $this->createAndActivateUser('user1@example.com');
        $this->user2 = $this->createAndActivateUser('user2@example.com');
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

