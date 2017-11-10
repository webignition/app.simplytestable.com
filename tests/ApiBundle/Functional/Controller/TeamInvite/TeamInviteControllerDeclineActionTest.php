<?php

namespace Tests\ApiBundle\Functional\Controller\TeamInvite;

use SimplyTestable\ApiBundle\Services\Team\InviteService;
use Tests\ApiBundle\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Request;

class TeamInviteControllerDeclineActionTest extends AbstractTeamInviteControllerTest
{
    public function testDeclineActionPostRequest()
    {
        $teamInviteService = $this->container->get(InviteService::class);

        $invitee = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'invitee@example.com',
        ]);

        $teamInviteService->get($this->users['leader'], $invitee);

        $router = $this->container->get('router');
        $requestUrl = $router->generate('teaminvite_decline');

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'GET',
            'user' => $this->users['leader'],
            'parameters' => [
                'team' => 'Foo',
            ],
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testDeclineActionInvalidTeam()
    {
        $teamInviteService = $this->container->get(InviteService::class);

        $user = $this->users['private'];
        $this->setUser($user);

        $this->assertFalse($teamInviteService->hasAnyForUser($user));

        $request = new Request([], [
            'team' => 'Invalid Team',
        ]);

        $response = $this->teamInviteController->declineAction($request);

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($teamInviteService->hasAnyForUser($user));
    }

    public function testDeclineActionValidTeamNoInvite()
    {
        $teamInviteService = $this->container->get(InviteService::class);

        $user = $this->users['private'];
        $this->setUser($user);

        $this->assertFalse($teamInviteService->hasAnyForUser($user));

        $request = new Request([], [
            'team' => 'Foo',
        ]);

        $response = $this->teamInviteController->declineAction($request);

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($teamInviteService->hasAnyForUser($user));
    }

    public function testDeclineActionValidTeamValidInvite()
    {
        $teamInviteService = $this->container->get(InviteService::class);

        $user = $this->users['private'];
        $this->setUser($user);

        $teamInviteService->get($this->users['leader'], $user);

        $this->assertTrue($teamInviteService->hasAnyForUser($user));

        $request = new Request([], [
            'team' => 'Foo',
        ]);

        $response = $this->teamInviteController->declineAction($request);

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($teamInviteService->hasAnyForUser($user));
    }
}
