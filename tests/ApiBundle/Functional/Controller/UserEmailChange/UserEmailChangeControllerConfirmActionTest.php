<?php

namespace Tests\ApiBundle\Functional\Controller\UserEmailChange;

use Tests\ApiBundle\Factory\UserFactory;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserEmailChangeControllerConfirmActionTest extends AbstractUserEmailChangeControllerTest
{
    public function testConfirmActionPostRequest()
    {
        $newEmail = 'new-email@example.com';
        $emailChangeRequest = $this->createEmailChangeRequest($this->user, $newEmail);

        $router = $this->container->get('router');
        $requestUrl = $router->generate('user_email_change_request_confirm', [
            'email_canonical' =>  $this->user->getEmail(),
            'token' => $emailChangeRequest->getToken(),
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'POST',
            'user' => $this->user,
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testConfirmActionUserHasNoRequest()
    {
        $this->expectException(NotFoundHttpException::class);

        $user = $this->userFactory->create([
            UserFactory::KEY_EMAIL => 'no-request@example.com',
        ]);

        $this->setUser($user);

        $this->userEmailChangeController->confirmAction(
            $user->getEmail(),
            'token'
        );
    }

    public function testConfirmActionInvalidToken()
    {
        $this->expectException(BadRequestHttpException::class);

        $this->createEmailChangeRequest($this->user, 'new-email@example.com');

        $this->userEmailChangeController->confirmAction(
            $this->user->getEmail(),
            'token'
        );
    }

    public function testConfirmActionNewEmailTaken()
    {
        $this->expectException(ConflictHttpException::class);

        $newEmail = 'new-email@example.com';

        $emailChangeRequest = $this->createEmailChangeRequest($this->user, $newEmail);

        $this->userFactory->create([
            UserFactory::KEY_EMAIL => $newEmail,
        ]);

        $this->userEmailChangeController->confirmAction(
            $this->user->getEmail(),
            $emailChangeRequest->getToken()
        );

        $this->assertNull($emailChangeRequest->getId());
    }

    public function testConfirmActionSuccess()
    {
        $newEmail = 'new-email@example.com';
        $emailChangeRequest = $this->createEmailChangeRequest($this->user, $newEmail);

        $response = $this->userEmailChangeController->confirmAction(
            $this->user->getEmail(),
            $emailChangeRequest->getToken()
        );

        $this->assertTrue($response->isSuccessful());
        $this->assertNull($emailChangeRequest->getId());
        $this->assertEquals($newEmail, $this->user->getEmail());
    }
}