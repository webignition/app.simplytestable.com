<?php

namespace App\Tests\Functional\Controller\User;

use App\Services\Team\InviteService;
use App\Services\UserService;
use App\Tests\Services\UserFactory;

/**
 * @group Controller/UserController
 */
class UserControllerHasInvitesActionTest extends AbstractUserControllerTest
{
    public function testHasInvitesActionGetRequest()
    {
        $teamInviteService = self::$container->get(InviteService::class);
        $userService = self::$container->get(UserService::class);

        $userFactory = self::$container->get(UserFactory::class);
        $users = $userFactory->createPublicPrivateAndTeamUserSet();

        $teamInviteService->get($users['leader'], $users['private']);

        $router = self::$container->get('router');
        $requestUrl = $router->generate('user_hasinvites', [
            'email_canonical' => $users['private']->getEmail(),
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
