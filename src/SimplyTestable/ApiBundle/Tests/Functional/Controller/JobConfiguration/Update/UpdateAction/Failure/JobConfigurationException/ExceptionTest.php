<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Update\UpdateAction\Failure\JobConfigurationException;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Update\UpdateAction\Failure\FailureTest;

abstract class ExceptionTest extends FailureTest {

    /**
     * @var User
     */
    private $user;


    /**
     * @return User
     */
    protected function getCurrentUser() {
        if (is_null($this->user)) {
            $userFactory = new UserFactory($this->container);

            $this->user = $userFactory->createAndActivateUser();
        }

        return $this->user;
    }
}