<?php

namespace App\Tests\Functional\Controller\User;

use App\Services\UserService;

/**
 * @group Controller/UserController
 */
class UserControllerExistsActionTest extends AbstractUserControllerTest
{
    public function testExistsActionGetRequest()
    {
        $userService = self::$container->get(UserService::class);
        $publicUser = $userService->getPublicUser();
        $adminUser = $userService->getAdminUser();

        $router = self::$container->get('router');
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
}
