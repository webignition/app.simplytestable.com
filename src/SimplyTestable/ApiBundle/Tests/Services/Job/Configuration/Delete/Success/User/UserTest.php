<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Delete\Success\User;

use SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Delete\Success\SuccessTest;

class UserTest extends SuccessTest {

    protected function getCurrentUser() {
        return $this->getUserService()->getPublicUser();
    }
}