<?php

namespace Tests\AppBundle\Functional\Controller\User;

use AppBundle\Services\UserService;

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
