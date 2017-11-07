<?php

namespace Tests\ApiBundle\Functional\Entity\User;

use Tests\ApiBundle\Functional\AbstractBaseTestCase;

class UserTest extends AbstractBaseTestCase
{
    public function testUtf8Email()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $userService = $this->container->get('simplytestable.services.userservice');
        $userRepository = $this->container->get('simplytestable.repository.user');

        $email = 'ɸ@example.com';

        $user = $userService->create($email, 'password');
        $userId = $user->getId();

        $entityManager->clear();

        $this->assertEquals($email, $userRepository->find($userId)->getEmail());
    }
}
