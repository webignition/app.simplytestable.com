<?php

namespace Tests\ApiBundle\Functional\Services\Team\Remove;

use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\Services\Team\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\Team\Exception as TeamServiceException;

class RemoveTest extends ServiceTest
{
    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * @var TeamService
     */
    private $teamService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userFactory = new UserFactory($this->container);
        $this->teamService = $this->container->get('simplytestable.services.teamservice');
    }

    public function testLeaderIsNotLeaderThrowsTeamServiceException() {
        $user1 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'user1@example.com',
        ]);
        $user2 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'user2@example.com',
        ]);

        $this->expectException(TeamServiceException::class);
        $this->expectExceptionCode(TeamServiceException::IS_NOT_LEADER);

        $this->teamService->remove($user1, $user2);
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

        $team1 = $this->teamService->create('Foo1', $leader1);
        $this->teamService->create('Foo2', $leader2);

        $teamMemberService->add($team1, $user);

        $this->expectException(TeamServiceException::class);
        $this->expectExceptionCode(TeamServiceException::USER_IS_NOT_ON_LEADERS_TEAM);

        $this->teamService->remove($leader2, $user);
    }


    public function testRemovesUserFromTeam() {
        $teamMemberService = $this->container->get('simplytestable.services.teammemberservice');

        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $user = $this->userFactory->createAndActivateUser();

        $team = $this->teamService->create('Foo', $leader);
        $teamMemberService->add($team, $user);

        $this->assertTrue($teamMemberService->contains($team, $user));

        $this->teamService->remove($leader, $user);

        $this->assertFalse($teamMemberService->contains($team, $user));
    }

}
