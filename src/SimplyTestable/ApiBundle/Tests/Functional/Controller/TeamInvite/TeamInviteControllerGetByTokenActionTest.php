<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\TeamInvite;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class TeamInviteControllerGetByTokenActionTest extends AbstractTeamInviteControllerTest
{
    public function testGetByTokenActionGetRequest()
    {
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
            'user' => $invitee,
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testGetByTokenActionNotFound()
    {
        $response = $this->teamInviteController->getByTokenAction('foo');

        $this->assertTrue($response->isNotFound());
    }

    public function testGetByTokenActionSuccess()
    {
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');

        $invitee = $this->userFactory->create([
            UserFactory::KEY_EMAIL => 'invitee@example.com',
        ]);

        $this->setUser($invitee);

        $invite = $teamInviteService->get($this->users['leader'], $invitee);

        $response = $this->teamInviteController->getByTokenAction($invite->getToken());

        $this->assertTrue($response->isSuccessful());

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals('Foo', $responseData['team']);
        $this->assertEquals($invitee->getEmail(), $responseData['user']);
        $this->assertEquals($invite->getToken(), $responseData['token']);
    }
}