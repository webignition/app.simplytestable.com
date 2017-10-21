<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Team;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    public function testGetActionUserNotOnTeam()
    {
        $this->setExpectedException(NotFoundHttpException::class);

        $this->setUser($this->users['private']);

        $this->teamController->getAction();
    }

    /**
     * @dataProvider getActionSuccessDataProvider
     *
     * @param string $userName
     */
    public function testGetActionSuccess($userName)
    {
        $this->setUser($this->users[$userName]);

        $response = $this->teamController->getAction();

        $this->assertTrue($response->isSuccessful());

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
