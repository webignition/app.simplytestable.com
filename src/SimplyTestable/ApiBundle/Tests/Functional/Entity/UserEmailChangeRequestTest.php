<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\UserEmailChangeRequest;

class UserEmailChangeRequestTest extends BaseSimplyTestableTestCase
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

        $userEmailChangeRequestRepository = $entityManager->getRepository(UserEmailChangeRequest::class);

        $this->assertEquals(
            $newEmail,
            $userEmailChangeRequestRepository->find($userEmailChangeRequestId)->getNewEmail()
        );
    }
}
