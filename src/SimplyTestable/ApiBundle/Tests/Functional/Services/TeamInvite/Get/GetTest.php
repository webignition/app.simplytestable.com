<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Team\TeamInvite\Get;

use SimplyTestable\ApiBundle\Services\Team\InviteService;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\TeamInvite\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\TeamInvite\Exception as TeamInviteServiceException;

class GetTest extends ServiceTest
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
     * @var InviteService
     */
    private $teamInviteService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userFactory = new UserFactory($this->container);
        $this->teamService = $this->container->get('simplytestable.services.teamservice');
        $this->teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');
    }

    public function testInviterIsNotTeamLeaderThrowsTeamInviteServiceException()
    {
        $user1 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'user1@example.com',
        ]);
        $user2 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'user2@example.com',
        ]);

        $this->expectException(TeamInviteServiceException::class);
        $this->expectExceptionCode(TeamInviteServiceException::INVITER_IS_NOT_A_LEADER);

        $this->teamInviteService->get(
            $user1,
            $user2
        );
    }

    public function testInviteeHasTeamThrowsTeamInviteServiceException()
    {
        $leader1 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader1@example.com',
        ]);
        $leader2 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader2@example.com',
        ]);

        $this->teamService->create(
            'Foo1',
            $leader1
        );

        $this->teamService->create(
            'Foo2',
            $leader2
        );

        $this->expectException(TeamInviteServiceException::class);
        $this->expectExceptionCode(TeamInviteServiceException::INVITEE_IS_A_LEADER);

        $this->teamInviteService->get(
            $leader1,
            $leader2
        );
    }

    public function testInviteeIsAlreadyInDifferentTeamThrowsTeamInviteServiceException()
    {
        $teamMemberService = $this->container->get('simplytestable.services.teammemberservice');

        $leader1 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader1@example.com',
        ]);
        $leader2 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader2@example.com',
        ]);
        $user = $this->userFactory->createAndActivateUser();

        $team1 = $this->teamService->create(
            'Foo1',
            $leader1
        );

        $this->teamService->create(
            'Foo2',
            $leader2
        );

        $teamMemberService->add($team1, $user);

        $this->expectException(TeamInviteServiceException::class);
        $this->expectExceptionCode(TeamInviteServiceException::INVITEE_IS_ON_A_TEAM);

        $this->teamInviteService->get(
            $leader2,
            $user
        );
    }

    public function testGetNewInviteReturnsInvite()
    {
        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $user = $this->userFactory->createAndActivateUser();

        $team = $this->teamService->create(
            'Foo1',
            $leader
        );

        $invite = $this->teamInviteService->get(
            $leader,
            $user
        );

        $this->assertNotNull($invite->getId());
        $this->assertEquals($team->getId(), $invite->getTeam()->getId());
        $this->assertEquals($user->getId(), $invite->getUser()->getId());
    }

    public function testGetExistingInviteReturnsExistingInvite()
    {
        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $user = $this->userFactory->createAndActivateUser();

        $this->teamService->create(
            'Foo1',
            $leader
        );

        $invite1 = $this->teamInviteService->get(
            $leader,
            $user
        );

        $invite2 = $this->teamInviteService->get(
            $leader,
            $user
        );

        $this->assertEquals($invite1, $invite2);
    }
}
