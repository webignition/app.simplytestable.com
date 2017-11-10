<?php

namespace Tests\ApiBundle\Functional\Controller\User;

use SimplyTestable\ApiBundle\Services\UserService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserControllerExistsActionTest extends AbstractUserControllerTest
{
    public function testExistsActionGetRequest()
    {
        $userService = $this->container->get(UserService::class);
        $publicUser = $userService->getPublicUser();
        $adminUser = $userService->getAdminUser();

        $router = $this->container->get('router');
        $requestUrl = $router->generate('user_exists', [
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

    public function testExistsActionUserNotFound()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->userController->existsAction('foo@example.com');
    }

    public function testExistsActionSuccess()
    {
        $userService = $this->container->get(UserService::class);
        $publicUser = $userService->getPublicUser();

        $response = $this->userController->existsAction($publicUser->getEmail());

        $this->assertTrue($response->isSuccessful());
    }
}
