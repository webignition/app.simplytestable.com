<?php

namespace Tests\ApiBundle\Functional\Controller\TeamInvite;

use SimplyTestable\ApiBundle\Services\Team\InviteService;
use Tests\ApiBundle\Factory\UserAccountPlanFactory;
use Tests\ApiBundle\Factory\UserFactory;

/**
 * @group Controller/TeamInviteController
 */
class TeamInviteControllerListActionTest extends AbstractTeamInviteControllerTest
{
    public function testListActionGetRequest()
    {
        $router = self::$container->get('router');
        $requestUrl = $router->generate('teaminvite_list');

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'GET',
            'user' => $this->users['leader'],
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testListActionClientFailure()
    {
        $response = $this->teamInviteController->listAction($this->users['private']);

        $this->assertTrue($response->isClientError());

        $this->assertEquals(
            [
                'code' => 1,
                'message' => 'User is not a team leader',
            ],
            [
                'code' => $response->headers->get('X-TeamInviteList-Error-Code'),
                'message' => $response->headers->get('X-TeamInviteList-Error-Message'),
            ]
        );
    }

    public function testListActionPremiumPlanUsersNotPresentInList()
    {
        $teamInviteService = self::$container->get(InviteService::class);

        $inviter = $this->users['leader'];

        $invitee = $this->userFactory->create([
            UserFactory::KEY_EMAIL => 'invitee@example.com',
        ]);

        $teamInviteService->get($inviter, $invitee);

        $response = $this->teamInviteController->listAction($inviter);

        $this->assertTrue($response->isSuccessful());

        $responseData = json_decode($response->getContent(), true);

        $this->assertCount(1, $responseData);
        $this->assertEquals('invitee@example.com', $responseData[0]['user']);

        $userAccountPlanFactory = new UserAccountPlanFactory(self::$container);
        $userAccountPlanFactory->create($invitee, 'agency');

        $response = $this->teamInviteController->listAction($inviter);

        $this->assertTrue($response->isSuccessful());

        $responseData = json_decode($response->getContent(), true);

        $this->assertEmpty($responseData);
    }

    /**
     * @dataProvider listActionSuccessDataProvider
     *
     * @param string[] $userEmailsToInvite
     * @param array[] $expectedInviteData
     */
    public function testListActionSuccess($userEmailsToInvite, $expectedInviteData)
    {
        $teamInviteService = self::$container->get(InviteService::class);

        $inviter = $this->users['leader'];

        foreach ($userEmailsToInvite as $userEmail) {
            $invitee = $this->userFactory->create([
                UserFactory::KEY_EMAIL => $userEmail,
            ]);

            $teamInviteService->get($inviter, $invitee);
        }

        $response = $this->teamInviteController->listAction($inviter);

        $this->assertTrue($response->isSuccessful());

        $responseData = json_decode($response->getContent(), true);

        $this->assertCount(count($expectedInviteData), $responseData);

        foreach ($responseData as $inviteIndex => $inviteData) {
            $expectedInvite = $expectedInviteData[$inviteIndex];

            $this->assertEquals(
                $expectedInvite['user'],
                $inviteData['user']
            );

            $this->assertEquals(
                'Foo',
                $inviteData['team']
            );
        }
    }

    /**
     * @return array
     */
    public function listActionSuccessDataProvider()
    {
        return [
            'no invites' => [
                'userEmailsToInvite' => [],
                'expectedInviteData' => [],
            ],
            'one invite' => [
                'userEmailsToInvite' => [
                    'user1@example.com',
                ],
                'expectedInviteData' => [
                    [
                        'user' => 'user1@example.com',
                    ],
                ],
            ],
            'many invites' => [
                'userEmailsToInvite' => [
                    'user1@example.com',
                    'user2@example.com',
                    'user3@example.com',
                ],
                'expectedInviteData' => [
                    [
                        'user' => 'user1@example.com',
                    ],
                    [
                        'user' => 'user2@example.com',
                    ],
                    [
                        'user' => 'user3@example.com',
                    ],
                ],
            ],
        ];
    }
}
