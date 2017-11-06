<?php

namespace Tests\ApiBundle\Functional\Controller\UserEmailChange;

use Tests\ApiBundle\Factory\UserFactory;

class UserEmailChangeControllerCancelActionTest extends AbstractUserEmailChangeControllerTest
{
    public function testCancelActionPostRequest()
    {
        $this->createEmailChangeRequest($this->user, 'new-email@example.com');

        $router = $this->container->get('router');
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

        $this->setUser($user);

        $response = $this->userEmailChangeController->cancelAction($user->getEmail());

        $this->assertTrue($response->isSuccessful());
    }

    public function testCancelActionSuccess()
    {
        $emailChangeRequestRepository = $this->container->get('simplytestable.repository.useremailchangerequest');

        $this->createEmailChangeRequest($this->user, 'new-email@example.com');

        $emailChangeRequest = $emailChangeRequestRepository->findOneBy([
            'user' => $this->user,
        ]);

        $response = $this->userEmailChangeController->cancelAction($this->user->getEmail());

        $this->assertTrue($response->isSuccessful());
        $this->assertNull($emailChangeRequest->getId());
    }
}