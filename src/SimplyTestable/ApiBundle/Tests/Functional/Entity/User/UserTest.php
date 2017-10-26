<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\User;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\User;

class UserTest extends BaseSimplyTestableTestCase
{
    public function testUtf8Email()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $userService = $this->container->get('simplytestable.services.userservice');

        $email = 'ɸ@example.com';

        $user = $userService->create($email, 'password');
        $userId = $user->getId();

        $entityManager->clear();

        $userRepository = $entityManager->getRepository(User::class);

        $this->assertEquals($email, $userRepository->find($userId)->getEmail());
    }
}
