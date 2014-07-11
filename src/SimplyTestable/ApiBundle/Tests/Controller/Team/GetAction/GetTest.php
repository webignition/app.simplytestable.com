<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Team\GetAction;

use SimplyTestable\ApiBundle\Tests\Controller\Team\ActionTest;

class GetTest extends ActionTest {

    public function testUserWithNoTeamReturnsNotFoundResponse() {
        $user = $this->createAndActivateUser('user@example.com', 'password');
        $this->getUserService()->setUser($user);

        $methodName = $this->getActionNameFromRouter();

        $this->assertEquals(404, $this->getCurrentController()->$methodName()->getStatusCode());
    }


    public function testWithLeaderOnly() {
        $leader = $this->createAndActivateUser('leader@example.com', 'password');

        $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $this->getUserService()->setUser($leader);

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController()->$methodName();

        $this->assertEquals(200, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('team', $decodedResponse);
        $this->assertEquals([
            'leader' => 'leader@example.com',
            'name' => 'Foo'
        ], $decodedResponse['team']);
    }

    public function testGetAsLeader() {
        $leader = $this->createAndActivateUser('leader@example.com', 'password');
        $member1 = $this->createAndActivateUser('member1@example.com', 'member1');
        $member2 = $this->createAndActivateUser('member2@example.com', 'member2');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $this->getTeamMemberService()->add($team, $member1);
        $this->getTeamMemberService()->add($team, $member2);

        $this->getUserService()->setUser($leader);

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController()->$methodName();

        $this->assertEquals(200, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('team', $decodedResponse);
        $this->assertArrayHasKey('members', $decodedResponse);

        $this->assertEquals([
            'leader' => 'leader@example.com',
            'name' => 'Foo'
        ], $decodedResponse['team']);

        $this->assertEquals([
            'member1@example.com',
            'member2@example.com'
        ], $decodedResponse['members']);
    }


    public function testGetAsMember() {
        $leader = $this->createAndActivateUser('leader@example.com', 'password');
        $member1 = $this->createAndActivateUser('member1@example.com', 'member1');
        $member2 = $this->createAndActivateUser('member2@example.com', 'member2');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $this->getTeamMemberService()->add($team, $member1);
        $this->getTeamMemberService()->add($team, $member2);

        $this->getUserService()->setUser($member1);

        $methodName = $this->getActionNameFromRouter();

        $response = $this->getCurrentController()->$methodName();

        $this->assertEquals(200, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('team', $decodedResponse);
        $this->assertArrayHasKey('members', $decodedResponse);

        $this->assertEquals([
            'leader' => 'leader@example.com',
            'name' => 'Foo'
        ], $decodedResponse['team']);

        $this->assertEquals([
            'member1@example.com',
            'member2@example.com'
        ], $decodedResponse['members']);
    }
    
}