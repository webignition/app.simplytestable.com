<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\TeamInvite\GetByTokenAction;

use SimplyTestable\ApiBundle\Controller\TeamInviteController;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;

class GetByTokenTest extends BaseControllerJsonTestCase {

    public function testInvalidTokenReturnsNotFoundResponse() {
        $teamInviteController = new TeamInviteController();
        $teamInviteController->setContainer($this->container);

        $response = $teamInviteController->getByTokenAction('foo');

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

        $teamInviteController = new TeamInviteController();
        $teamInviteController->setContainer($this->container);

        $response = $teamInviteController->getByTokenAction($invite->getToken());

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