<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\User;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\User;

class UserTest extends BaseSimplyTestableTestCase {

    public function testUtf8Email() {
        $userService = $this->container->get('simplytestable.services.userservice');

        $email = 'ɸ@example.com';

        $user = $userService->create($email, 'password');
        $userId = $user->getId();

        $this->getManager()->clear();
        $this->assertEquals($email, $this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\User')->find($userId)->getEmail());
    }
}
