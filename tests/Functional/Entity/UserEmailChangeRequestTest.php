<?php

namespace App\Tests\Functional\Entity;

use App\Services\UserService;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Entity\UserEmailChangeRequest;

class UserEmailChangeRequestTest extends AbstractBaseTestCase
{
    public function testUtf8NewEmail()
    {
        $userService = self::$container->get(UserService::class);
        $entityManager = self::$container->get('doctrine.orm.entity_manager');

        $newEmail = 'foo-ɸ@example.com';
        $userEmailChangeRequest = new UserEmailChangeRequest();

        $userEmailChangeRequest->setUser($userService->create('user@example.com', 'password'));
        $userEmailChangeRequest->setNewEmail($newEmail);
        $userEmailChangeRequest->setToken('foo-token');

        $entityManager->persist($userEmailChangeRequest);
        $entityManager->flush();

        $userEmailChangeRequestId = $userEmailChangeRequest->getId();

        $entityManager->clear();

        $userEmailChangeRequestRepository = $entityManager->getRepository(UserEmailChangeRequest::class);

        $this->assertEquals(
            $newEmail,
            $userEmailChangeRequestRepository->find($userEmailChangeRequestId)->getNewEmail()
        );
    }
}
