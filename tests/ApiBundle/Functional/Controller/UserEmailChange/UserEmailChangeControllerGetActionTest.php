<?php

namespace Tests\ApiBundle\Functional\Controller\UserEmailChange;

use SimplyTestable\ApiBundle\Entity\UserEmailChangeRequest;
use SimplyTestable\ApiBundle\Services\UserService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserEmailChangeControllerGetActionTest extends AbstractUserEmailChangeControllerTest
{
    public function testGetActionGetRequest()
    {
        $userService = $this->container->get(UserService::class);

        $this->createEmailChangeRequest($this->user, 'new-email@example.com');

        $router = $this->container->get('router');
        $requestUrl = $router->generate('user_email_change_request_get', [
            'email_canonical' =>  $this->user->getEmail(),
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'GET',
            'user' => $userService->getAdminUser(),
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testGetActionInvalidUser()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->userEmailChangeController->getAction('foo@example.com');
    }

    public function testGetActionNoEmailChangeRequest()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->userEmailChangeController->getAction($this->user->getEmail());
    }

    public function testGetActionSuccess()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $emailChangeRequestRepository = $entityManager->getRepository(UserEmailChangeRequest::class);

        $this->createEmailChangeRequest($this->user, 'new-email@example.com');

        $emailChangeRequest = $emailChangeRequestRepository->findOneBy([
            'user' => $this->user,
        ]);

        $response = $this->userEmailChangeController->getAction($this->user->getEmail());

        $this->assertTrue($response->isSuccessful());

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals([
            'new_email' => $emailChangeRequest->getNewEmail(),
            'token' => $emailChangeRequest->getToken(),
        ], $responseData);
    }
}
