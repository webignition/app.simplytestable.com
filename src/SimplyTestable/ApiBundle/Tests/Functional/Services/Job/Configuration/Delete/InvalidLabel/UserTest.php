<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Delete\InvalidLabel;

class UserTest extends InvalidLabelTest {

    protected function getCurrentUser() {
        $userService = $this->container->get('simplytestable.services.userservice');
        return $userService->getPublicUser();
    }
}