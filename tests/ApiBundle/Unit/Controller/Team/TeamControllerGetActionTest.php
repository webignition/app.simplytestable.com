<?php

namespace Tests\ApiBundle\Unit\Controller\Team;

use SimplyTestable\ApiBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use Tests\ApiBundle\Factory\MockFactory;

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
