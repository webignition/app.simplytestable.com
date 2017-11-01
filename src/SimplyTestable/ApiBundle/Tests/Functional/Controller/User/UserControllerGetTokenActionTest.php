<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\User;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserControllerGetTokenActionTest extends AbstractUserControllerTest
{
    public function testGetTokenActionGetRequest()
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $publicUser = $userService->getPublicUser();
        $adminUser = $userService->getAdminUser();

        $router = $this->container->get('router');
        $requestUrl = $router->generate('user_get_token', [
            'email_canonical' => $publicUser->getEmail(),
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'GET',
            'user' => $adminUser,
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testGetTokenActionUserNotFound()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->userController->getTokenAction('foo@example.com');
    }

    public function testGetTokenActionSuccess()
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $publicUser = $userService->getPublicUser();

        $response = $this->userController->getTokenAction($publicUser->getEmail());

        $this->assertTrue($response->isSuccessful());

        $this->assertEquals(
            $userService->getConfirmationToken($publicUser),
            json_decode($response->getContent())
        );
    }
}
