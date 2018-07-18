<?php

namespace Tests\AppBundle\Functional\Controller\User;

use AppBundle\Services\UserService;
use Tests\AppBundle\Factory\UserFactory;

/**
 * @group Controller/UserController
 */
class UserControllerIsEnabledActionTest extends AbstractUserControllerTest
{
    public function testIsEnabledActionGetRequest()
    {
        $userService = self::$container->get(UserService::class);

        $userFactory = new UserFactory(self::$container);
        $user = $userFactory->createAndActivateUser([
            UserFactory::KEY_PLAN_NAME => null,
        ]);

        $router = self::$container->get('router');
        $requestUrl = $router->generate('user_is_enabled', [
            'email_canonical' => $user->getEmail(),
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'GET',
            'user' => $userService->getAdminUser(),
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }
}
