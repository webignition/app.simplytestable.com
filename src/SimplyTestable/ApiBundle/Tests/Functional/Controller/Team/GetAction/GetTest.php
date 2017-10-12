<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Team\GetAction;

use SimplyTestable\ApiBundle\Controller\TeamController;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;

class GetTest extends BaseControllerJsonTestCase
{
    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * @var TeamController
     */
    private $teamController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userFactory = new UserFactory($this->container);
        $this->teamController = new TeamController();
        $this->teamController->setContainer($this->container);
    }

    public function testUserWithNoTeamReturnsNotFoundResponse() {
        $user = $this->userFactory->createAndActivateUser();
        $this->setUser($user);

        $response = $this->teamController->getAction();

        $this->assertEquals(404, $response->getStatusCode());
    }


    public function testWithLeaderOnly() {
        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);

        $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $this->setUser($leader);

        $response = $this->teamController->getAction();

        $this->assertEquals(200, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('team', $decodedResponse);
        $this->assertEquals([
            'leader' => 'leader@example.com',
            'name' => 'Foo'
        ], $decodedResponse['team']);
    }

    public function testGetAsLeader() {
        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $member1 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'member1@example.com',
        ]);
        $member2 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'member2@example.com',
        ]);

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $this->getTeamMemberService()->add($team, $member1);
        $this->getTeamMemberService()->add($team, $member2);

        $this->setUser($leader);

        $response = $this->teamController->getAction();

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
        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $member1 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'member1@example.com',
        ]);
        $member2 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'member2@example.com',
        ]);

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $this->getTeamMemberService()->add($team, $member1);
        $this->getTeamMemberService()->add($team, $member2);

        $this->setUser($member1);

        $response = $this->teamController->getAction();

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