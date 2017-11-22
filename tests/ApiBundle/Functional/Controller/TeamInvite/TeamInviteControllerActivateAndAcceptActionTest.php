<?php

namespace Tests\ApiBundle\Functional\Controller\TeamInvite;

use SimplyTestable\ApiBundle\Services\Team\InviteService;
use SimplyTestable\ApiBundle\Services\Team\MemberService;
use Tests\ApiBundle\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group Controller/TeamInviteController
 */
class TeamInviteControllerActivateAndAcceptActionTest extends AbstractTeamInviteControllerTest
{
    public function testActivateAndAcceptActionPostRequest()
    {
        $teamInviteService = $this->container->get(InviteService::class);

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
        $teamInviteService = $this->container->get(InviteService::class);
        $teamMemberService = $this->container->get(MemberService::class);

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
