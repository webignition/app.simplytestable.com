<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Team\CreateAction;

class CreateTest extends ActionTest {

    public function testRequestAsTeamLeaderReturnsExistingTeam() {
        $leader = $this->createAndActivateUser('leader@example.com', 'password');

        $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $this->getUserService()->setUser($leader);

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController([
            'name' => 'Foo'
        ])->$methodName();

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/team/', $response->headers->get('location'));
        $this->assertEquals('Foo', $response->headers->get('X-Team-Name'));
    }


    public function testRequestAsTeamMemberReturnsExistingTeam() {
        $leader = $this->createAndActivateUser('leader@example.com', 'password');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $user = $this->createAndActivateUser('user@example.com', 'password');
        $this->getUserService()->setUser($user);

        $this->getTeamMemberService()->add($team, $user);

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController([
            'name' => 'Foo'
        ])->$methodName();

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/team/', $response->headers->get('location'));
        $this->assertEquals('Foo', $response->headers->get('X-Team-Name'));
    }


    public function testRequestAsNonLeaderAndNonMemberCreatesNewTeam() {
        $user = $this->createAndActivateUser('user@example.com', 'password');
        $this->getUserService()->setUser($user);

        $this->assertNull($this->getTeamService()->getForUser($user));

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController([
            'name' => 'Foo'
        ])->$methodName();

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/team/', $response->headers->get('location'));
        $this->assertEquals('Foo', $response->headers->get('X-Team-Name'));

        $this->assertNotNull($this->getTeamService()->getForUser($user));
    }
    
}