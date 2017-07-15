<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Team\RemoveAction;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\Team\ActionTest;

class RemoveTest extends ActionTest {

    public function testMemberIsNotUserReturnsBadRequest() {
        $leader = $this->createAndActivateUser('leader@example.com');

        $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController()->$methodName('user@example.com');

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(9, $response->headers->get('X-TeamRemove-Error-Code'));
        $this->assertEquals('Member is not a user', $response->headers->get('X-TeamRemove-Error-Message'));
    }


    public function testLeaderIsNotALeaderReturnsBadRequest() {
        $user1 = $this->createAndActivateUser('user1@example.com');
        $user2 = $this->createAndActivateUser('user2@example.com');

        $this->getUserService()->setUser($user1);

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController()->$methodName($user2->getEmail());

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(5, $response->headers->get('X-TeamRemove-Error-Code'));
        $this->assertEquals('User is not a leader', $response->headers->get('X-TeamRemove-Error-Message'));
    }


    public function testUserIsNotInLeadersTeamThrowsTeamServiceException() {
        $leader1 = $this->createAndActivateUser('leader1@example.com');
        $leader2 = $this->createAndActivateUser('leader2@example.com');
        $user = $this->createAndActivateUser('user@example.com');

        $team1 = $this->getTeamService()->create('Foo1', $leader1);
        $this->getTeamService()->create('Foo2', $leader2);

        $this->getTeamMemberService()->add($team1, $user);

        $this->getUserService()->setUser($leader2);

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController()->$methodName('user@example.com');

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(6, $response->headers->get('X-TeamRemove-Error-Code'));
        $this->assertEquals('User is not on leader\'s team', $response->headers->get('X-TeamRemove-Error-Message'));
    }


    public function testRemovesUserFromTeam() {
        $leader = $this->createAndActivateUser('leader@example.com');
        $user = $this->createAndActivateUser('user@example.com');

        $team = $this->getTeamService()->create('Foo', $leader);
        $this->getTeamMemberService()->add($team, $user);

        $this->assertTrue($this->getTeamMemberService()->contains($team, $user));

        $this->getUserService()->setUser($leader);

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController()->$methodName('user@example.com');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($this->getTeamMemberService()->contains($team, $user));
    }


    /**
     *
     * @return array
     */
    protected function getRouteParameters() {
        return [
            'member_email' => 'user@example.com'
        ];
    }

}