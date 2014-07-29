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

        $responseObject = json_decode($response->getContent(), true);

        $this->assertEquals(1, count($responseObject));

        $responseInvite = $responseObject[0];

        $this->assertEquals($invite->getUser()->getUsername(), $responseInvite['user']);
        $this->assertEquals($invite->getTeam()->getName(), $responseInvite['team']);
        $this->assertNotNull($responseInvite['token']);
    }


    public function testPremiumIndividualUsersExcludedFromInviteList() {
        $leader = $this->createAndActivateUser('leader@example.com');
        $user1 = $this->createAndActivateUser('user1@example.com');
        $user2 = $this->createAndActivateUser('user2@example.com');

        $this->getUserService()->setUser($leader);

        $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $this->getTeamInviteService()->get($leader, $user1);
        $comparatorInvite = $this->getTeamInviteService()->get($leader, $user2);

        $this->getUserAccountPlanService()->subscribe($user1, $this->getAccountPlanService()->find('personal'));

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController()->$methodName();

        $this->assertEquals(200, $response->getStatusCode());

        $responseObject = json_decode($response->getContent(), true);

        $this->assertEquals(1, count($responseObject));

        $responseInvite = $responseObject[0];

        $this->assertEquals($comparatorInvite->getUser()->getUsername(), $responseInvite['user']);
        $this->assertEquals($comparatorInvite->getTeam()->getName(), $responseInvite['team']);
        $this->assertNotNull($responseInvite['token']);
    }


    /**
     *
     * @return array
     */
    protected function getRouteParameters() {
        return [];
    }
    
}