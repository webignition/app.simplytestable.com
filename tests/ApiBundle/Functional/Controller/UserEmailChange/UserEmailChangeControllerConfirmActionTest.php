<?php

namespace Tests\ApiBundle\Functional\Controller\UserEmailChange;

use Tests\ApiBundle\Factory\UserFactory;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @group Controller/UserEmailChangeController
 */
class UserEmailChangeControllerConfirmActionTest extends AbstractUserEmailChangeControllerTest
{
    public function testConfirmActionPostRequest()
    {
        $newEmail = 'new-email@example.com';
        $emailChangeRequest = $this->createEmailChangeRequest($this->user, $newEmail);

        $router = self::$container->get('router');
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

    public function testConfirmActionSuccess()
    {
        $newEmail = 'new-email@example.com';
        $emailChangeRequest = $this->createEmailChangeRequest($this->user, $newEmail);

        $response = $this->userEmailChangeController->confirmAction(
            $this->user,
            $this->user->getEmail(),
            $emailChangeRequest->getToken()
        );

        $this->assertTrue($response->isSuccessful());
        $this->assertNull($emailChangeRequest->getId());
        $this->assertEquals($newEmail, $this->user->getEmail());
    }
}
