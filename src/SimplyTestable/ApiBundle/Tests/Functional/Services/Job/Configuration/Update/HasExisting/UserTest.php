<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Update\HasExisting;

class UserTest extends HasExistingTest {

    protected function getCurrentUser() {
        $userService = $this->container->get('simplytestable.services.userservice');
        return $userService->getPublicUser();
    }
}