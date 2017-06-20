<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Delete\Success\User;

use SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Delete\Success\SuccessTest;

class UserTest extends SuccessTest {

    protected function getCurrentUser() {
        return $this->getUserService()->getPublicUser();
    }
}