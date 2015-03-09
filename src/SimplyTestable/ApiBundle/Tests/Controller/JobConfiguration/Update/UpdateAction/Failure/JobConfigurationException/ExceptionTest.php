<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Update\UpdateAction\Failure\JobConfigurationException;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Update\UpdateAction\Failure\FailureTest;

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
            $this->user = $this->createAndActivateUser('user@example.com');
        }

        return $this->user;
    }
}