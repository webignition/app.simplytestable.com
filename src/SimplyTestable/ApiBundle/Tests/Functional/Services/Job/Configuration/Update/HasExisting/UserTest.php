<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Update\HasExisting;

class UserTest extends HasExistingTest {

    protected function getCurrentUser() {
        return $this->getUserService()->getPublicUser();
    }
}