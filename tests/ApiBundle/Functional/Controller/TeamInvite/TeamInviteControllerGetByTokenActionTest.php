<?php

namespace Tests\ApiBundle\Functional\Controller\TeamInvite;

use SimplyTestable\ApiBundle\Services\UserService;
use Tests\ApiBundle\Factory\UserFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TeamInviteControllerGetByTokenActionTest extends AbstractTeamInviteControllerTest
{
    public function testGetByTokenActionGetRequest()
    {
        $userService = $this->container->get(UserService::class);
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');

        $invitee = $this->userFactory->create([
            UserFactory::KEY_EMAIL => 'invitee@example.com',
        ]);

        $invite = $teamInviteService->get($this->users['leader'], $invitee);

        $router = $this->container->get('router');
        $requestUrl = $router->generate('teaminvite_getbytoken', [
            'token' => $invite->getToken(),
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'GET',
            'user' => $userService->getAdminUser(),
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testGetByTokenActionNotFound()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->teamInviteController->getByTokenAction('foo');
    }

    public function testGetByTokenActionSuccess()
    {
        $userService = $this->container->get(UserService::class);
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');

        $invitee = $this->userFactory->create([
            UserFactory::KEY_EMAIL => 'invitee@example.com',
        ]);

        $this->setUser($userService->getAdminUser());

        $invite = $teamInviteService->get($this->users['leader'], $invitee);

        $response = $this->teamInviteController->getByTokenAction($invite->getToken());

        $this->assertTrue($response->isSuccessful());

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals('Foo', $responseData['team']);
        $this->assertEquals($invitee->getEmail(), $responseData['user']);
        $this->assertEquals($invite->getToken(), $responseData['token']);
    }
}
