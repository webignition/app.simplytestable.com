<?php

namespace App\Tests\Functional\Controller\TeamInvite;

use App\Services\Team\InviteService;
use App\Tests\Factory\UserAccountPlanFactory;

/**
 * @group Controller/TeamInviteController
 */
class TeamInviteControllerUserListActionTest extends AbstractTeamInviteControllerTest
{
    public function testUserListActionGetRequest()
    {
        $router = self::$container->get('router');
        $requestUrl = $router->generate('teaminvite_userlist');

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'GET',
            'user' => $this->users['private'],
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testUserListActionNoInvites()
    {
        $user = $this->users['private'];

        $response = $this->teamInviteController->userListAction($user);

        $this->assertTrue($response->isSuccessful());

        $responseData = json_decode($response->getContent(), true);

        $this->assertEmpty($responseData);
    }

    public function testUserListActionHasInvites()
    {
        $teamInviteService = self::$container->get(InviteService::class);

        $user = $this->users['private'];
        $teamInviteService->get($this->users['leader'], $user);

        $response = $this->teamInviteController->userListAction($user);

        $this->assertTrue($response->isSuccessful());

        $responseData = json_decode($response->getContent(), true);

        $this->assertCount(1, $responseData);

        $inviteData = $responseData[0];
        $this->assertEquals('Foo', $inviteData['team']);
        $this->assertEquals($user->getEmail(), $inviteData['user']);
    }

    public function testUserListActionHasInvitesPremiumPlanUser()
    {
        $teamInviteService = self::$container->get(InviteService::class);

        $user = $this->users['private'];
        $teamInviteService->get($this->users['leader'], $user);

        $userAccountPlanFactory = new UserAccountPlanFactory(self::$container);
        $userAccountPlanFactory->create($user, 'agency');

        $response = $this->teamInviteController->userListAction($user);

        $this->assertTrue($response->isSuccessful());

        $responseData = json_decode($response->getContent(), true);

        $this->assertEmpty($responseData);
    }
}
