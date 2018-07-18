<?php

namespace App\Tests\Functional\Entity\User;

use App\Entity\User;
use App\Services\UserService;
use App\Tests\Functional\AbstractBaseTestCase;

class UserTest extends AbstractBaseTestCase
{
    public function testUtf8Email()
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $userService = self::$container->get(UserService::class);

        $userRepository = $entityManager->getRepository(User::class);

        $email = 'ɸ@example.com';

        $user = $userService->create($email, 'password');
        $userId = $user->getId();

        $entityManager->clear();

        $this->assertEquals($email, $userRepository->find($userId)->getEmail());
    }
}
