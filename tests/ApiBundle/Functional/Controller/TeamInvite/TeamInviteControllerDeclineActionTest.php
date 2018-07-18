<?php

namespace Tests\ApiBundle\Functional\Controller\TeamInvite;

use SimplyTestable\ApiBundle\Services\Team\InviteService;
use Tests\ApiBundle\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group Controller/TeamInviteController
 */
class TeamInviteControllerDeclineActionTest extends AbstractTeamInviteControllerTest
{
    public function testDeclineActionPostRequest()
    {
        $teamInviteService = self::$container->get(InviteService::class);

        $invitee = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'invitee@example.com',
        ]);

        $teamInviteService->get($this->users['leader'], $invitee);

        $router = self::$container->get('router');
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
        $teamInviteService = self::$container->get(InviteService::class);

        $user = $this->users['private'];

        $this->assertFalse($teamInviteService->hasAnyForUser($user));

        $request = new Request([], [
            'team' => 'Invalid Team',
        ]);

        $response = $this->teamInviteController->declineAction($user, $request);

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($teamInviteService->hasAnyForUser($user));
    }

    public function testDeclineActionValidTeamNoInvite()
    {
        $teamInviteService = self::$container->get(InviteService::class);

        $user = $this->users['private'];

        $this->assertFalse($teamInviteService->hasAnyForUser($user));

        $request = new Request([], [
            'team' => 'Foo',
        ]);

        $response = $this->teamInviteController->declineAction($user, $request);

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($teamInviteService->hasAnyForUser($user));
    }

    public function testDeclineActionValidTeamValidInvite()
    {
        $teamInviteService = self::$container->get(InviteService::class);

        $user = $this->users['private'];

        $teamInviteService->get($this->users['leader'], $user);

        $this->assertTrue($teamInviteService->hasAnyForUser($user));

        $request = new Request([], [
            'team' => 'Foo',
        ]);

        $response = $this->teamInviteController->declineAction($user, $request);

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($teamInviteService->hasAnyForUser($user));
    }
}
