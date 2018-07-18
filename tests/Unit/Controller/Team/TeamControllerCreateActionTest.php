<?php

namespace App\Tests\Unit\Controller\Team;

use App\Entity\User;
use App\Services\UserService;
use Symfony\Component\HttpFoundation\Request;
use App\Tests\Factory\MockFactory;

/**
 * @group Controller/TeamController
 */
class TeamControllerCreateActionTest extends AbstractTeamControllerTest
{
    public function testCreateActionSpecialUser()
    {
        $user = new User();

        $userService = MockFactory::createUserService([
            'isSpecialUser' => [
                'with' => $user,
                'return' => true,
            ],
        ]);

        $teamController = $this->createTeamController([
            UserService::class => $userService,
        ]);
        $response = $teamController->createAction(new Request(), $user);

        $this->assertTrue($response->isClientError());

        $this->assertEquals(
            [
                'code' => 9,
                'message' => 'Special users cannot create teams',
            ],
            [
                'code' => $response->headers->get('X-TeamCreate-Error-Code'),
                'message' => $response->headers->get('X-TeamCreate-Error-Message'),
            ]
        );
    }
}
