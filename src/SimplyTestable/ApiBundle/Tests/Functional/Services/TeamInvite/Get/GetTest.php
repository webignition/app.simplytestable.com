<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Team\TeamInvite\Get;

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
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userFactory = new UserFactory($this->container);
    }

    public function testInviterIsNotTeamLeaderThrowsTeamInviteServiceException()
    {
        $user1 = $this->userFactory->createAndActivateUser('user1@example.com');
        $user2 = $this->userFactory->createAndActivateUser('user2@example.com');

        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\TeamInvite\Exception',
            '',
            TeamInviteServiceException::INVITER_IS_NOT_A_LEADER
        );

        $this->getTeamInviteService()->get(
            $user1,
            $user2
        );
    }

    public function testInviteeHasTeamThrowsTeamInviteServiceException()
    {
        $leader1 = $this->userFactory->createAndActivateUser('leader1@example.com');
        $leader2 = $this->userFactory->createAndActivateUser('leader2@example.com');

        $this->getTeamService()->create(
            'Foo1',
            $leader1
        );

        $this->getTeamService()->create(
            'Foo2',
            $leader2
        );

        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\TeamInvite\Exception',
            '',
            TeamInviteServiceException::INVITEE_IS_A_LEADER
        );

        $this->getTeamInviteService()->get(
            $leader1,
            $leader2
        );
    }

    public function testInviteeIsAlreadyInDifferentTeamThrowsTeamInviteServiceException()
    {
        $leader1 = $this->userFactory->createAndActivateUser('leader1@example.com');
        $leader2 = $this->userFactory->createAndActivateUser('leader2@example.com');
        $user = $this->userFactory->createAndActivateUser('user@example.com');

        $team1 = $this->getTeamService()->create(
            'Foo1',
            $leader1
        );

        $this->getTeamService()->create(
            'Foo2',
            $leader2
        );

        $this->getTeamMemberService()->add($team1, $user);

        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\TeamInvite\Exception',
            '',
            TeamInviteServiceException::INVITEE_IS_ON_A_TEAM
        );

        $this->getTeamInviteService()->get(
            $leader2,
            $user
        );
    }

    public function testGetNewInviteReturnsInvite()
    {
        $leader = $this->userFactory->createAndActivateUser('leader@example.com');
        $user = $this->userFactory->createAndActivateUser('user@example.com');

        $team = $this->getTeamService()->create(
            'Foo1',
            $leader
        );

        $invite = $this->getTeamInviteService()->get(
            $leader,
            $user
        );

        $this->assertNotNull($invite->getId());
        $this->assertEquals($team->getId(), $invite->getTeam()->getId());
        $this->assertEquals($user->getId(), $invite->getUser()->getId());
    }

    public function testGetExistingInviteReturnsExistingInvite()
    {
        $leader = $this->userFactory->createAndActivateUser('leader@example.com');
        $user = $this->userFactory->createAndActivateUser('user@example.com');

        $this->getTeamService()->create(
            'Foo1',
            $leader
        );

        $invite1 = $this->getTeamInviteService()->get(
            $leader,
            $user
        );

        $invite2 = $this->getTeamInviteService()->get(
            $leader,
            $user
        );

        $this->assertEquals($invite1, $invite2);
    }
}
