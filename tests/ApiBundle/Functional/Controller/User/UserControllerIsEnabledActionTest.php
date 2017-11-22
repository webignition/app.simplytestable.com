<?php

namespace Tests\ApiBundle\Functional\Controller\User;

use SimplyTestable\ApiBundle\Services\UserService;
use Tests\ApiBundle\Factory\UserFactory;

/**
 * @group Controller/UserController
 */
class UserControllerIsEnabledActionTest extends AbstractUserControllerTest
{
    public function testIsEnabledActionGetRequest()
    {
        $userService = $this->container->get(UserService::class);

        $userFactory = new UserFactory($this->container);
        $user = $userFactory->createAndActivateUser([
            UserFactory::KEY_PLAN_NAME => null,
        ]);

        $router = $this->container->get('router');
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
