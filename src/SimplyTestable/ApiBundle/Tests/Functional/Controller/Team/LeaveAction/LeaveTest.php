<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Team\LeaveAction;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\Team\ActionTest;

class LeaveTest extends ActionTest
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

    public function testLeaderReturnsBadRequest() {
        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);

        $this->getTeamService()->create('Foo', $leader);

        $this->getUserService()->setUser($leader);

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController()->$methodName('user@example.com');

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(9, $response->headers->get('X-TeamLeave-Error-Code'));
        $this->assertEquals('Leader cannot leave team', $response->headers->get('X-TeamLeave-Error-Message'));
    }

    public function testUserNotOnTeamReturnsOkResponse() {
        $user = $this->userFactory->createAndActivateUser();

        $this->getUserService()->setUser($user);

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController()->$methodName('user@example.com');

        $this->assertEquals(200, $response->getStatusCode());
    }


    public function testUserInTeamLeavesTeam() {
        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $user = $this->userFactory->createAndActivateUser();

        $team = $this->getTeamService()->create('Foo', $leader);
        $this->getTeamMemberService()->add($team, $user);

        $this->assertTrue($this->getTeamMemberService()->contains($team, $user));

        $this->getUserService()->setUser($user);

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController()->$methodName('user@example.com');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($this->getTeamMemberService()->contains($team, $user));
    }

}