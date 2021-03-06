<?php

namespace App\Tests\Functional\Controller\Team;

use App\Services\Team\Service;
use App\Tests\Services\UserFactory;

/**
 * @group Controller/TeamController
 */
class TeamControllerRemoveActionTest extends AbstractTeamControllerTest
{
    public function testRemoveActionPostRequest()
    {
        $router = self::$container->get('router');
        $requestUrl = $router->generate('team_remove', [
            'member_email' => 'member1@example.com',
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'POST',
            'user' => $this->users['leader'],
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    /**
     * @dataProvider removeActionClientFailureDataProvider
     *
     * @param string $userName
     * @param string $memberEmail
     * @param array $expectedResponseError
     */
    public function testRemoveActionClientFailure($userName, $memberEmail, $expectedResponseError)
    {
        $user = $this->users[$userName];
        $this->setUser($user);

        $response = $this->teamController->removeAction($user, $memberEmail);

        $this->assertTrue($response->isClientError());

        $this->assertEquals(
            $expectedResponseError,
            [
                'code' => $response->headers->get('X-TeamRemove-Error-Code'),
                'message' => $response->headers->get('X-TeamRemove-Error-Message'),
            ]
        );
    }

    /**
     * @return array
     */
    public function removeActionClientFailureDataProvider()
    {
        return [
            'requested member is not a user' => [
                'userName' => 'leader',
                'memberEmail' => 'foo@example.com',
                'expectedResponseError' => [
                    'code' => 9,
                    'message' => 'Member is not a user',
                ],
            ],
            'user member is not team leader' => [
                'userName' => 'member1',
                'memberEmail' => 'member2@example.com',
                'expectedResponseError' => [
                    'code' => 5,
                    'message' => 'User is not a leader',
                ],
            ],
            'requested member is not in user\'s team' => [
                'userName' => 'leader',
                'memberEmail' => 'private@example.com',
                'expectedResponseError' => [
                    'code' => 6,
                    'message' => 'User is not on leader\'s team',
                ],
            ],
        ];
    }

    /**
     * @dataProvider removeActionSuccessDataProvider
     *
     * @param string $memberEmail
     */
    public function testRemoveActionSuccess($memberEmail)
    {
        $teamService = self::$container->get(Service::class);
        $member = $this->userFactory->create([
            UserFactory::KEY_EMAIL => $memberEmail,
        ]);

        $leader = $this->users['leader'];
        $this->setUser($leader);

        $response = $this->teamController->removeAction($leader, $memberEmail);

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($teamService->hasForUser($member));
    }

    /**
     * @return array
     */
    public function removeActionSuccessDataProvider()
    {
        return [
            'remove member1' => [
                'memberEmail' => 'member1@example.com',
            ],
            'remove member2' => [
                'memberEmail' => 'member2@example.com',
            ],
        ];
    }
}
