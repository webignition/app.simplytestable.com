<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Team\GetAction;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\Team\ActionTest;

class GetTest extends ActionTest
{
    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userFactory = new UserFactory($this->container);
    }

    public function testUserWithNoTeamReturnsNotFoundResponse() {
        $user = $this->userFactory->createAndActivateUser();
        $this->getUserService()->setUser($user);

        $methodName = $this->getActionNameFromRouter();

        $this->assertEquals(404, $this->getCurrentController()->$methodName()->getStatusCode());
    }


    public function testWithLeaderOnly() {
        $leader = $this->userFactory->createAndActivateUser('leader@example.com');

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
        $leader = $this->userFactory->createAndActivateUser('leader@example.com');
        $member1 = $this->userFactory->createAndActivateUser('member1@example.com');
        $member2 = $this->userFactory->createAndActivateUser('member2@example.com');

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
        $leader = $this->userFactory->createAndActivateUser('leader@example.com');
        $member1 = $this->userFactory->createAndActivateUser('member1@example.com');
        $member2 = $this->userFactory->createAndActivateUser('member2@example.com');

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