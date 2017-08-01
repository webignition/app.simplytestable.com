<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\Get;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class DirectOwnerTest extends IsRetrievedTest {

    /**
     * @var User
     */
    private $user;

    protected function setUpPreCreate() {
        $userFactory = new UserFactory($this->container);

        $this->user = $userFactory->createAndActivateUser();
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