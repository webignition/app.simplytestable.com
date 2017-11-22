<?php

namespace Tests\ApiBundle\Functional\Controller\Team;

/**
 * @group Controller/TeamController
 */
class TeamControllerGetActionTest extends AbstractTeamControllerTest
{
    public function testGetActionGetRequest()
    {
        $router = $this->container->get('router');
        $requestUrl = $router->generate('team_get');

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'GET',
            'user' => $this->users['leader'],
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    /**
     * @dataProvider getActionSuccessDataProvider
     *
     * @param string $userName
     */
    public function testGetActionSuccess($userName)
    {
        $user = $this->users[$userName];
        $response = $this->teamController->getAction($user);

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('application/json', $response->headers->get('content-type'));

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(
            [
                'team' => [
                    'leader' => 'leader@example.com',
                    'name' => 'Foo',
                ],
                'members' => [
                    'member1@example.com',
                    'member2@example.com',
                ],
            ],
            $responseData
        );
    }

    /**
     * @return array
     */
    public function getActionSuccessDataProvider()
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
}
