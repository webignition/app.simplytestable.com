<?php

namespace Tests\AppBundle\Functional\Controller\User;

use AppBundle\Services\UserService;

/**
 * @group Controller/UserController
 */
class UserControllerGetTokenActionTest extends AbstractUserControllerTest
{
    public function testGetTokenActionGetRequest()
    {
        $userService = self::$container->get(UserService::class);
        $publicUser = $userService->getPublicUser();
        $adminUser = $userService->getAdminUser();

        $router = self::$container->get('router');
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
}
