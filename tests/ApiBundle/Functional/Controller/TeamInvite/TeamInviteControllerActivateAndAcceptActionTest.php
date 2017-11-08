<?php

namespace Tests\ApiBundle\Functional\Controller\TeamInvite;

use Tests\ApiBundle\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Request;

class TeamInviteControllerActivateAndAcceptActionTest extends AbstractTeamInviteControllerTest
{
    public function testActivateAndAcceptActionPostRequest()
    {
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');

        $inviteeUser = $this->userFactory->create([
            UserFactory::KEY_EMAIL => 'invitee@example.com',
        ]);

        $invite = $teamInviteService->get($this->users['leader'], $inviteeUser);

        $router = $this->container->get('router');
        $requestUrl = $router->generate('teaminvite_activateandaccept');

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'POST',
            'user' => null,
            'parameters' => [
                'token' => $invite->getToken(),
                'password' => 'user password choice',
            ],
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testActivateAndAcceptActionClientFailure()
    {
        $response = $this->teamInviteController->activateAndAcceptAction(new Request());

        $this->assertTrue($response->isClientError());
    }

    public function testActivateAndAcceptActionSuccess()
    {
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');
        $teamMemberService = $this->container->get('simplytestable.services.teammemberservice');

        $inviteeUser = $this->userFactory->create([
            UserFactory::KEY_EMAIL => 'invitee@example.com',
        ]);

        $initialUserPassword = $inviteeUser->getPassword();

        $invite = $teamInviteService->get($this->users['leader'], $inviteeUser);

        $this->assertFalse($inviteeUser->isEnabled());
        $this->assertFalse($teamMemberService->belongsToTeam($inviteeUser));

        $response = $this->teamInviteController->activateAndAcceptAction(new Request([], [
            'token' => $invite->getToken(),
            'password' => 'user password choice',
        ]));

        $this->assertTrue($response->isSuccessful());

        $this->assertTrue($inviteeUser->isEnabled());
        $this->assertFalse($teamInviteService->hasAnyForUser($inviteeUser));

        $team = $teamMemberService->getTeamByMember($inviteeUser);
        $this->assertEquals($invite->getTeam(), $team);
        $this->assertNotEquals($initialUserPassword, $inviteeUser->getPassword());
    }
}
