<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\TeamInvite;

use SimplyTestable\ApiBundle\Tests\Factory\UserAccountPlanFactory;

class TeamInviteControllerUserListActionTest extends AbstractTeamInviteControllerTest
{
    public function testUserListActionGetRequest()
    {
        $router = $this->container->get('router');
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

        $this->setUser($user);

        $response = $this->teamInviteController->userListAction();

        $this->assertTrue($response->isSuccessful());

        $responseData = json_decode($response->getContent(), true);

        $this->assertEmpty($responseData);
    }

    public function testUserListActionHasInvites()
    {
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');

        $user = $this->users['private'];
        $teamInviteService->get($this->users['leader'], $user);

        $this->setUser($user);

        $response = $this->teamInviteController->userListAction();

        $this->assertTrue($response->isSuccessful());

        $responseData = json_decode($response->getContent(), true);

        $this->assertCount(1, $responseData);

        $inviteData = $responseData[0];
        $this->assertEquals('Foo', $inviteData['team']);
        $this->assertEquals($user->getEmail(), $inviteData['user']);
    }

    public function testUserListActionHasInvitesPremiumPlanUser()
    {
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');

        $user = $this->users['private'];
        $teamInviteService->get($this->users['leader'], $user);

        $userAccountPlanFactory = new UserAccountPlanFactory($this->container);
        $userAccountPlanFactory->create($user, 'agency');

        $this->setUser($user);

        $response = $this->teamInviteController->userListAction();

        $this->assertTrue($response->isSuccessful());

        $responseData = json_decode($response->getContent(), true);

        $this->assertEmpty($responseData);
    }
}
