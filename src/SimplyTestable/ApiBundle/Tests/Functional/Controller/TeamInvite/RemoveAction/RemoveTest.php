<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\TeamInvite\RemoveAction;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\TeamInvite\ActionTest;

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

    public function testUserIsNotTeamLeaderReturnsBadRequest() {
        $user = $this->userFactory->createAndActivateUser();
        $this->getUserService()->setUser($user);

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController()->$methodName('user@example.com');
        $this->assertEquals(1, $response->headers->get('X-TeamInviteRemove-Error-Code'));
        $this->assertEquals('User is not a team leader', $response->headers->get('X-TeamInviteRemove-Error-Message'));
    }


    public function testInviteeIsNotAUserReturnsBadRequest() {
        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $this->getUserService()->setUser($leader);

        $this->getTeamService()->create('Foo', $leader);

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController()->$methodName('user@example.com');

        $this->assertEquals(2, $response->headers->get('X-TeamInviteRemove-Error-Code'));
        $this->assertEquals('Invitee is not a user', $response->headers->get('X-TeamInviteRemove-Error-Message'));
    }


    public function testInviteeDoesNotHaveAnInviteReturnsBadRequest() {
        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $user = $this->userFactory->createAndActivateUser();
        $this->getUserService()->setUser($leader);

        $this->getTeamService()->create('Foo', $leader);

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController()->$methodName($user->getEmail());

        $this->assertEquals(3, $response->headers->get('X-TeamInviteRemove-Error-Code'));
        $this->assertEquals('Invitee does not have an invite for this team', $response->headers->get('X-TeamInviteRemove-Error-Message'));
    }


    public function testInviteIsNotForUsersTeamReturnsBadRequest() {
        $leader1 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader1@example.com',
        ]);
        $leader2 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader2@example.com',
        ]);
        $user = $this->userFactory->createAndActivateUser();

        $this->getTeamService()->create('Foo1', $leader1);
        $this->getTeamService()->create('Foo2', $leader2);

        $this->getTeamInviteService()->get($leader1, $user);

        $this->getUserService()->setUser($leader2);

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController()->$methodName($user->getEmail());

        $this->assertEquals(3, $response->headers->get('X-TeamInviteRemove-Error-Code'));
        $this->assertEquals('Invitee does not have an invite for this team', $response->headers->get('X-TeamInviteRemove-Error-Message'));
    }


    public function testInviteIsRemoved() {
        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $user = $this->userFactory->createAndActivateUser();

        $team = $this->getTeamService()->create('Foo', $leader);
        $this->getTeamInviteService()->get($leader, $user);

        $this->assertTrue($this->getTeamInviteService()->hasForTeamAndUser($team, $user));

        $this->getUserService()->setUser($leader);

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController()->$methodName($user->getEmail());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($this->getTeamInviteService()->hasForTeamAndUser($team, $user));
    }


    /**
     *
     * @return array
     */
    protected function getRouteParameters() {
        return [
            'invitee_email' => 'user@example.com'
        ];
    }

}