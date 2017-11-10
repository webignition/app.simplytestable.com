<?php

namespace Tests\ApiBundle\Functional\Controller\Team;

use SimplyTestable\ApiBundle\Services\Team\Service;

class TeamControllerLeaveActionTest extends AbstractTeamControllerTest
{
    public function testLeaveActionPostRequest()
    {
        $router = $this->container->get('router');
        $requestUrl = $router->generate('team_leave');

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'POST',
            'user' => $this->users['member1'],
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testLeaveActionLeaderCannotLeave()
    {
        $user = $this->users['leader'];
        $this->setUser($user);

        $response = $this->teamController->leaveAction();

        $this->assertTrue($response->isClientError());

        $this->assertEquals(
            [
                'code' => 9,
                'message' => 'Leader cannot leave team',
            ],
            [
                'code' => $response->headers->get('X-TeamLeave-Error-Code'),
                'message' => $response->headers->get('X-TeamLeave-Error-Message'),
            ]
        );
    }

    /**
     * @dataProvider leaveActionSuccessDataProvider
     *
     * @param string $userName
     */
    public function testLeaveActionSuccess($userName)
    {
        $teamService = $this->container->get(Service::class);

        $user = $this->users[$userName];
        $this->setUser($user);

        $response = $this->teamController->leaveAction();

        $this->assertTrue($response->isSuccessful());

        $this->assertFalse($teamService->hasForUser($user));
    }

    /**
     * @return array
     */
    public function leaveActionSuccessDataProvider()
    {
        return [
            'not on a team' => [
                'userName' => 'private',
            ],
            'on a team' => [
                'userName' => 'member1',
            ],
        ];
    }
}
