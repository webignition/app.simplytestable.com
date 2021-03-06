<?php

namespace App\Tests\Functional\Controller\UserEmailChange;

use App\Entity\UserEmailChangeRequest;
use App\Tests\Services\UserFactory;

/**
 * @group Controller/UserEmailChangeController
 */
class UserEmailChangeControllerCancelActionTest extends AbstractUserEmailChangeControllerTest
{
    public function testCancelActionPostRequest()
    {
        $this->createEmailChangeRequest($this->user, 'new-email@example.com');

        $router = self::$container->get('router');
        $requestUrl = $router->generate('user_email_change_request_cancel', [
            'email_canonical' =>  $this->user->getEmail(),
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'POST',
            'user' => $this->user,
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testCancelActionUserHasNoRequest()
    {
        $user = $this->userFactory->create([
            UserFactory::KEY_EMAIL => 'no-request@example.com',
        ]);

        $response = $this->userEmailChangeController->cancelAction($user, $user->getEmail());

        $this->assertTrue($response->isSuccessful());
    }

    public function testCancelActionSuccess()
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $emailChangeRequestRepository = $entityManager->getRepository(UserEmailChangeRequest::class);

        $this->createEmailChangeRequest($this->user, 'new-email@example.com');

        $emailChangeRequest = $emailChangeRequestRepository->findOneBy([
            'user' => $this->user,
        ]);

        $response = $this->userEmailChangeController->cancelAction($this->user, $this->user->getEmail());

        $this->assertTrue($response->isSuccessful());
        $this->assertNull($emailChangeRequest->getId());
    }
}
