<?php

namespace Tests\ApiBundle\Functional\Controller\User;

use SimplyTestable\ApiBundle\Services\Team\InviteService;
use SimplyTestable\ApiBundle\Services\UserService;
use Tests\ApiBundle\Factory\UserFactory;

/**
 * @group Controller/UserController
 */
class UserControllerHasInvitesActionTest extends AbstractUserControllerTest
{
    public function testHasInvitesActionGetRequest()
    {
        $teamInviteService = self::$container->get(InviteService::class);
        $userService = self::$container->get(UserService::class);

        $userFactory = new UserFactory(self::$container);
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
