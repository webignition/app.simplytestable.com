<?php

namespace App\Tests\Functional\Controller\TeamInvite;

use App\Services\Team\InviteService;
use App\Tests\Services\UserFactory;

/**
 * @group Controller/TeamInviteController
 */
class TeamInviteControllerRemoveActionTest extends AbstractTeamInviteControllerTest
{
    public function testRemoveActionGetRequest()
    {
        $teamInviteService = self::$container->get(InviteService::class);

        $invitee = $this->userFactory->create([
            UserFactory::KEY_EMAIL => 'invitee@example.com',
        ]);

        $teamInviteService->get($this->users['leader'], $invitee);

        $router = self::$container->get('router');
        $requestUrl = $router->generate('teaminvite_remove', [
            'invitee_email' => $invitee->getEmail(),
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'GET',
            'user' => $this->users['leader'],
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    /**
     * @dataProvider removeActionClientFailureDataProvider
     *
     * @param string $userName
     * @param string $inviteeEmail
     * @param array $expectedResponseError
     */
    public function testRemoveActionClientFailure($userName, $inviteeEmail, $expectedResponseError)
    {
        $user = $this->users[$userName];

        $response = $this->teamInviteController->removeAction($user, $inviteeEmail);

        $this->assertTrue($response->isClientError());

        $this->assertEquals(
            $expectedResponseError,
            [
                'code' => $response->headers->get('X-TeamInviteRemove-Error-Code'),
                'message' => $response->headers->get('X-TeamInviteRemove-Error-Message'),
            ]
        );
    }

    /**
     * @return array
     */
    public function removeActionClientFailureDataProvider()
    {
        return [
            'user is not a team leader' => [
                'userName' => 'private',
                'inviteeEmail' => 'foo@example.com',
                'expectedResponseError' => [
                    'code' => 1,
                    'message' => 'User is not a team leader',
                ],
            ],
            'invitee is not a user' => [
                'userName' => 'leader',
                'inviteeEmail' => 'foo@example.com',
                'expectedResponseError' => [
                    'code' => 2,
                    'message' => 'Invitee is not a user',
                ],
            ],
            'invitee does not have an invite' => [
                'userName' => 'leader',
                'inviteeEmail' => 'member1@example.com',
                'expectedResponseError' => [
                    'code' => 3,
                    'message' => 'Invitee does not have an invite for this team',
                ],
            ],
        ];
    }

    public function testRemoveActionSuccess()
    {
        $teamInviteService = self::$container->get(InviteService::class);

        $invitee = $this->userFactory->create([
            UserFactory::KEY_EMAIL => 'invitee@example.com',
        ]);

        $teamInviteService->get($this->users['leader'], $invitee);

        $user = $this->users['leader'];

        $response = $this->teamInviteController->removeAction($user, $invitee->getEmail());

        $this->assertTrue($response->isSuccessful());

        $this->assertEmpty($teamInviteService->getForUser($invitee));
    }
}
