<?php

namespace SimplyTestable\ApiBundle\Tests\Services\ScheduledJob\Get;

use SimplyTestable\ApiBundle\Entity\User;

class DirectOwnerTest extends IsRetrievedTest {

    /**
     * @var User
     */
    private $user;

    protected function setUpPreCreate() {
        $this->user = $this->createAndActivateUser('user@example.com');
    }

    protected function getJobConfigurationOwner()
    {
        return $this->user;
    }

    protected function getServiceRequestUser()
    {
        return $this->user;
    }
}