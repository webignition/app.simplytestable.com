<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\TeamInvite\GetByTokenAction;

use SimplyTestable\ApiBundle\Tests\Controller\TeamInvite\ActionTest;

class GetByTokenTest extends ActionTest {

    public function testInvalidTokenReturnsNotFoundResponse() {
        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController()->$methodName('foo');

        $this->assertEquals(404, $response->getStatusCode());
    }


    public function testTokenReturnsInvite() {
        $inviter = $this->createAndActivateUser('inviter@example.com', 'password');
        $invitee = $this->createAndActivateUser('invitee@example.com', 'password');

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