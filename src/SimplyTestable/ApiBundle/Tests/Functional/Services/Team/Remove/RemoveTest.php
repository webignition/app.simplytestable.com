<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Team\Remove;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\Team\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\Team\Exception as TeamServiceException;

class RemoveTest extends ServiceTest
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

    public function testLeaderIsNotLeaderThrowsTeamServiceException() {
        $user1 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'user1@example.com',
        ]);
        $user2 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'user2@example.com',
        ]);

        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Team\Exception',
            '',
            TeamServiceException::IS_NOT_LEADER
        );

        $this->getTeamService()->remove($user1, $user2);
    }


    public function testUserIsNotInLeadersTeamThrowsTeamServiceException() {
        $teamMemberService = $this->container->get('simplytestable.services.teammemberservice');

        $leader1 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader1@example.com',
        ]);
        $leader2 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader2@example.com',
        ]);
        $user = $this->userFactory->createAndActivateUser();

        $team1 = $this->getTeamService()->create('Foo1', $leader1);
        $this->getTeamService()->create('Foo2', $leader2);

        $teamMemberService->add($team1, $user);

        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Team\Exception',
            '',
            TeamServiceException::USER_IS_NOT_ON_LEADERS_TEAM
        );

        $this->getTeamService()->remove($leader2, $user);
    }


    public function testRemovesUserFromTeam() {
        $teamMemberService = $this->container->get('simplytestable.services.teammemberservice');

        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $user = $this->userFactory->createAndActivateUser();

        $team = $this->getTeamService()->create('Foo', $leader);
        $teamMemberService->add($team, $user);

        $this->assertTrue($teamMemberService->contains($team, $user));

        $this->getTeamService()->remove($leader, $user);

        $this->assertFalse($teamMemberService->contains($team, $user));
    }

}
