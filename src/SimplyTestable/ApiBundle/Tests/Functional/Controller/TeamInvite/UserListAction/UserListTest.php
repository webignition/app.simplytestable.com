<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\TeamInvite\UserListAction;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\TeamInvite\ActionTest;

class UserListTest extends ActionTest
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

    public function testNoInvitesReturnsEmptyCollection() {
        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
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
        $leader1 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader1@example.com',
        ]);
        $leader2 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader2@example.com',
        ]);
        $user = $this->userFactory->createAndActivateUser();

        $this->getUserService()->setUser($user);

        $this->getTeamService()->create(
            'Foo1',
            $leader1
        );

        $this->getTeamService()->create(
            'Foo2',
            $leader2
        );

        $invite1 = $this->getTeamInviteService()->get($leader1, $user);
        $invite2 = $this->getTeamInviteService()->get($leader2, $user);

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController()->$methodName();

        $this->assertEquals(200, $response->getStatusCode());

        $responseObject = json_decode($response->getContent(), true);

        $this->assertEquals(2, count($responseObject));

        $this->assertEquals($invite1->getUser()->getUsername(), $responseObject[0]['user']);
        $this->assertEquals($invite1->getTeam()->getName(), $responseObject[0]['team']);
        $this->assertNotNull($responseObject[0]['token']);

        $this->assertEquals($invite2->getUser()->getUsername(), $responseObject[1]['user']);
        $this->assertEquals($invite2->getTeam()->getName(), $responseObject[1]['team']);
        $this->assertNotNull($responseObject[1]['token']);
    }


    public function testUserOnPremiumPlanListsNoInvites() {
        $leader1 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader1@example.com',
        ]);
        $leader2 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader2@example.com',
        ]);
        $user = $this->userFactory->createAndActivateUser();

        $this->getUserService()->setUser($user);

        $this->getTeamService()->create(
            'Foo1',
            $leader1
        );

        $this->getTeamService()->create(
            'Foo2',
            $leader2
        );

        $this->getTeamInviteService()->get($leader1, $user);
        $this->getTeamInviteService()->get($leader2, $user);

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController()->$methodName();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], json_decode($response->getContent(), true));
    }


    /**
     *
     * @return array
     */
    protected function getRouteParameters() {
        return [];
    }

}