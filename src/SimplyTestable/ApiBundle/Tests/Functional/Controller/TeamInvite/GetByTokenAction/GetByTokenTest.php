<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\TeamInvite\GetByTokenAction;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\TeamInvite\ActionTest;

class GetByTokenTest extends ActionTest {

    public function testInvalidTokenReturnsNotFoundResponse() {
        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController()->$methodName('foo');

        $this->assertEquals(404, $response->getStatusCode());
    }


    public function testTokenReturnsInvite() {
        $userFactory = new UserFactory($this->container);

        $inviter = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'inviter@example.com',
        ]);
        $invitee = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'invitee@example.com',
        ]);

        $this->getTeamService()->create(
            'Foo',
            $inviter
        );

        $invite = $this->getTeamInviteService()->get($inviter, $invitee);
        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController()->$methodName($invite->getToken());

        $this->assertEquals(200, $response->getStatusCode());

        $responseObject = json_decode($response->getContent(), true);

        $this->assertEquals($invite->getUser()->getUsername(), $responseObject['user']);
        $this->assertEquals($invite->getTeam()->getName(), $responseObject['team']);
        $this->assertEquals($invite->getToken(), $responseObject['token']);
    }


    /**
     *
     * @return array
     */
    protected function getRouteParameters() {
        return [
            'token' => 'foo'
        ];
    }

}