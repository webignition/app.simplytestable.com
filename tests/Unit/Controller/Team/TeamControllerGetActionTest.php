<?php

namespace App\Tests\Unit\Controller\Team;

use App\Entity\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Services\Team\Service as TeamService;
use App\Tests\Factory\MockFactory;

/**
 * @group Controller/TeamController
 */
class TeamControllerGetActionTest extends AbstractTeamControllerTest
{
    public function testGetActionUserNotOnTeam()
    {
        $user = new User();

        $teamController = $this->createTeamController([
            TeamService::class => MockFactory::createTeamService([
                'getForUser' => [
                    'with' => $user,
                    'return' => null,
                ],
            ]),
        ]);

        $this->expectException(NotFoundHttpException::class);

        $teamController->getAction($user);
    }
}
