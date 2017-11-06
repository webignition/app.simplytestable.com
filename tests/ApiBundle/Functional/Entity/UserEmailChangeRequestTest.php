<?php

namespace Tests\ApiBundle\Functional\Entity;

use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Entity\UserEmailChangeRequest;

class UserEmailChangeRequestTest extends AbstractBaseTestCase
{
    public function testUtf8NewEmail()
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $newEmail = 'foo-ɸ@example.com';
        $userEmailChangeRequest = new UserEmailChangeRequest();

        $userEmailChangeRequest->setUser($userService->create('user@example.com', 'password'));
        $userEmailChangeRequest->setNewEmail($newEmail);
        $userEmailChangeRequest->setToken('foo-token');

        $entityManager->persist($userEmailChangeRequest);
        $entityManager->flush();

        $userEmailChangeRequestId = $userEmailChangeRequest->getId();

        $entityManager->clear();

        $userEmailChangeRequestRepository = $this->container->get('simplytestable.repository.useremailchangerequest');

        $this->assertEquals(
            $newEmail,
            $userEmailChangeRequestRepository->find($userEmailChangeRequestId)->getNewEmail()
        );
    }
}
