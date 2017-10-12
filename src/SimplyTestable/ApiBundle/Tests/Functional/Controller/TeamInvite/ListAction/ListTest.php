<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\TeamInvite\ListAction;

use SimplyTestable\ApiBundle\Controller\TeamInviteController;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;

class ListTest extends BaseControllerJsonTestCase
{
    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * @var TeamInviteController
     */
    private $teamInviteController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userFactory = new UserFactory($this->container);
        $this->teamInviteController = new TeamInviteController();
        $this->teamInviteController->setContainer($this->container);
    }

    public function testUserIsNotTeamLeaderReturnsBadRequest() {
        $user = $this->userFactory->createAndActivateUser();
        $this->setUser($user);

        $response = $this->teamInviteController->listAction();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(1, $response->headers->get('X-TeamInviteList-Error-Code'));
        $this->assertEquals('User is not a team leader', $response->headers->get('X-TeamInviteList-Error-Message'));
    }

    public function testNoInvitesReturnsEmptyCollection() {
        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $this->setUser($leader);

        $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $response = $this->teamInviteController->listAction();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], json_decode($response->getContent(), true));
    }


    public function testHasInvitesReturnsInviteCollection() {
        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $user = $this->userFactory->createAndActivateUser();

        $this->setUser($leader);

        $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $invite = $this->getTeamInviteService()->get($leader, $user);

        $response = $this->teamInviteController->listAction();

        $this->assertEquals(200, $response->getStatusCode());

        $responseObject = json_decode($response->getContent(), true);

        $this->assertEquals(1, count($responseObject));

        $responseInvite = $responseObject[0];

        $this->assertEquals($invite->getUser()->getUsername(), $responseInvite['user']);
        $this->assertEquals($invite->getTeam()->getName(), $responseInvite['team']);
        $this->assertNotNull($responseInvite['token']);
    }


    public function testPremiumIndividualUsersExcludedFromInviteList() {
        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $user1 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'user1@example.com',
        ]);
        $user2 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'user2@example.com',
        ]);

        $this->setUser($leader);

        $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $this->getTeamInviteService()->get($leader, $user1);
        $comparatorInvite = $this->getTeamInviteService()->get($leader, $user2);

        $this->getUserAccountPlanService()->subscribe($user1, $this->getAccountPlanService()->find('personal'));

        $response = $this->teamInviteController->listAction();

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