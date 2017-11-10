<?php

namespace Tests\ApiBundle\Functional\Controller\User;

use SimplyTestable\ApiBundle\Services\UserService;
use Tests\ApiBundle\Factory\UserFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    public function testIsEnabledActionUnknownUser()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->userController->isEnabledAction('foo@example.com');
    }

    public function testIsEnabledActionNotEnabledUser()
    {
        $this->expectException(NotFoundHttpException::class);

        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create([
            UserFactory::KEY_PLAN_NAME => null,
        ]);

        $this->userController->isEnabledAction($user->getEmail());
    }

    public function testIsEnabledActionSuccess()
    {
        $userFactory = new UserFactory($this->container);
        $user = $userFactory->createAndActivateUser([
            UserFactory::KEY_PLAN_NAME => null,
        ]);

        $response = $this->userController->isEnabledAction($user->getEmail());

        $this->assertTrue($response->isSuccessful());
    }
}
