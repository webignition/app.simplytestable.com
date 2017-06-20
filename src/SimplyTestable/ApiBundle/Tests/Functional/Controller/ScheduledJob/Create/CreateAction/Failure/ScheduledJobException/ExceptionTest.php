<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Create\CreateAction\Failure\ScheduledJobException;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Create\CreateAction\Failure\FailureTest;

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