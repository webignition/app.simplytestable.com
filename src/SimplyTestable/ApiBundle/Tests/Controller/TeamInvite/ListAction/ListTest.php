<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\TeamInvite\ListAction;

use SimplyTestable\ApiBundle\Tests\Controller\TeamInvite\ActionTest;

class ListTest extends ActionTest {

    public function testUserIsNotTeamLeaderReturnsBadRequest() {
        $user = $this->createAndActivateUser('user@example.com');
        $this->getUserService()->setUser($user);

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController()->$methodName();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(1, $response->headers->get('X-TeamInviteList-Error-Code'));
        $this->assertEquals('User is not a team leader', $response->headers->get('X-TeamInviteList-Error-Message'));
    }

    public function testNoInvitesReturnsEmptyCollection() {
        $leader = $this->createAndActivateUser('leader@example.com');
        $this->getUserService()->setUser($leader);

        $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController()->$methodName();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], json_decode($response->getContent(), true));
    }


    public function testHasInvitesReturnsInviteCollection() {
        $leader = $this->createAndActivateUser('leader@example.com');
        $user = $this->createAndActivateUser('user@example.com');

        $this->getUserService()->setUser($leader);

        $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $invite = $this->getTeamInviteService()->get($leader, $user);

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController()->$methodName();

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals([
            [
                'user' => $invite->getUser()->getUsername(),
                'token' => $invite->getToken()
            ]
        ], json_decode($response->getContent(), true));
    }


    /**
     *
     * @return array
     */
    protected function getRouteParameters() {
        return [];
    }
    
}