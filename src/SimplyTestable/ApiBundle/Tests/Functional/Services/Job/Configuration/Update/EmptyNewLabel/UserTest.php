<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Update\EmptyNewLabel;

class UserTest extends EmptyNewLabelTest {

    protected function getCurrentUser() {
        $userService = $this->container->get('simplytestable.services.userservice');
        return $userService->getPublicUser();
    }
}