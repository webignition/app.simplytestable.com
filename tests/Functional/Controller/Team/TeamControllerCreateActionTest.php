<?php

namespace App\Tests\Functional\Controller\Team;

use App\Services\Team\InviteService;
use App\Services\Team\Service;
use App\Tests\Services\UserFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group Controller/TeamController
 */
class TeamControllerCreateActionTest extends AbstractTeamControllerTest
{
    public function testCreateActionPostRequest()
    {
        $router = self::$container->get('router');
        $requestUrl = $router->generate('team_create');

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'POST',
            'user' => $this->users['leader'],
            'parameters' => [
                'name' => 'Foo',
            ],
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isRedirect('http://localhost/team/'));
    }

    /**
     * @dataProvider createActionClientFailureDataProvider
     *
     * @param string $userEmail
     * @param array $postData
     * @param array $expectedResponseError
     */
    public function testCreateActionClientFailure($userEmail, $postData, $expectedResponseError)
    {
        $userFactory = self::$container->get(UserFactory::class);
        $user = $userFactory->create([
            UserFactory::KEY_EMAIL => $userEmail,
        ]);

        $this->setUser($user);

        $request = new Request([], $postData);
        $response = $this->teamController->createAction($request, $user);

        $this->assertTrue($response->isClientError());

        $this->assertEquals(
            $expectedResponseError,
            [
                'code' => $response->headers->get('X-TeamCreate-Error-Code'),
                'message' => $response->headers->get('X-TeamCreate-Error-Message'),
            ]
        );
    }

    /**
     * @return array
     */
    public function createActionClientFailureDataProvider()
    {
        return [
            'name empty' => [
                'userEmail' => 'new-user@example.com',
                'postData' => [],
                'expectedResponseError' => [
                    'code' => 1,
                    'message' => 'Team name cannot be empty',
                ],
            ],
            'name taken' => [
                'userEmail' => 'new-user@example.com',
                'postData' => [
                    'name' => 'Foo',
                ],
                'expectedResponseError' => [
                    'code' => 2,
                    'message' => 'Team name is already taken',
                ],
            ],
        ];
    }

    /**
     * @dataProvider createActionExistingTeamDataProvider
     *
     * @param string $userName
     */
    public function testCreateActionExistingTeam($userName)
    {
        $user = $this->users[$userName];
        $this->setUser($user);

        $request = new Request([], [
            'team' => 'Foo',
        ]);

        $response = $this->teamController->createAction($request, $user);

        $this->assertTrue($response->isRedirect('http://localhost/team/'));
        $this->assertEquals('Foo', $response->headers->get('X-Team-Name'));
    }

    /**
     * @return array
     */
    public function createActionExistingTeamDataProvider()
    {
        return [
            'leader' => [
                'userName' => 'leader',
            ],
            'member1' => [
                'userName' => 'member1',
            ],
        ];
    }

    public function testCreateActionNoExistingTeam()
    {
        $teamInviteService = self::$container->get(InviteService::class);
        $teamService = self::$container->get(Service::class);

        $user = $this->userFactory->create([
            UserFactory::KEY_EMAIL => 'new-user@example.com',
        ]);

        $teamInviteService->get($this->users['leader'], $user);

        $this->assertTrue($teamInviteService->hasAnyForUser($user));

        $this->setUser($user);

        $request = new Request([], [
            'name' => 'Unique Team Name',
        ]);

        $response = $this->teamController->createAction($request, $user);

        $this->assertTrue($response->isRedirect('http://localhost/team/'));
        $this->assertEquals('Unique Team Name', $response->headers->get('X-Team-Name'));

        $team = $teamService->getForUser($user);

        $this->assertFalse($teamInviteService->hasAnyForUser($user));
        $this->assertEquals('Unique Team Name', $team->getName());
    }
}
