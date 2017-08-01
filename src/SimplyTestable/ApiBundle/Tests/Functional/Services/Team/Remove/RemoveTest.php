<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Team\Create;

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
        $user1 = $this->userFactory->createAndActivateUser('user1@example.com');
        $user2 = $this->userFactory->createAndActivateUser('user2@example.com');

        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Team\Exception',
            '',
            TeamServiceException::IS_NOT_LEADER
        );

        $this->getTeamService()->remove($user1, $user2);
    }


    public function testUserIsNotInLeadersTeamThrowsTeamServiceException() {
        $leader1 = $this->userFactory->createAndActivateUser('leader1@example.com');
        $leader2 = $this->userFactory->createAndActivateUser('leader2@example.com');
        $user = $this->userFactory->createAndActivateUser();

        $team1 = $this->getTeamService()->create('Foo1', $leader1);
        $this->getTeamService()->create('Foo2', $leader2);

        $this->getTeamMemberService()->add($team1, $user);

        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Team\Exception',
            '',
            TeamServiceException::USER_IS_NOT_ON_LEADERS_TEAM
        );

        $this->getTeamService()->remove($leader2, $user);
    }


    public function testRemovesUserFromTeam() {
        $leader = $this->userFactory->createAndActivateUser('leader@example.com');
        $user = $this->userFactory->createAndActivateUser();

        $team = $this->getTeamService()->create('Foo', $leader);
        $this->getTeamMemberService()->add($team, $user);

        $this->assertTrue($this->getTeamMemberService()->contains($team, $user));

        $this->getTeamService()->remove($leader, $user);

        $this->assertFalse($this->getTeamMemberService()->contains($team, $user));
    }

}
