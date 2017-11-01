<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\TeamMember\Add;

use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\TeamMember\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\TeamMember\Exception as TeamMemberServiceException;

class CreateTest extends ServiceTest
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

    public function testUserAlreadyOnTeamThrowsTeamMemberServiceException()
    {
        $teamMemberService = $this->container->get('simplytestable.services.teammemberservice');

        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);

        $team = $this->teamService->create(
            'Foo',
            $leader
        );

        $user = $this->userFactory->createAndActivateUser();

        $teamMemberService->add($team, $user);

        $this->expectException(TeamMemberServiceException::class);
        $this->expectExceptionCode(TeamMemberServiceException::USER_ALREADY_ON_TEAM);

        $teamMemberService->add($team, $user);
    }

    public function testUserNotAlreadyOnTeamIsAdded()
    {
        $teamMemberService = $this->container->get('simplytestable.services.teammemberservice');

        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);

        $team = $this->teamService->create(
            'Foo',
            $leader
        );

        $user = $this->userFactory->createAndActivateUser();

        $member = $teamMemberService->add($team, $user);

        $this->assertInstanceOf('SimplyTestable\ApiBundle\Entity\Team\Member', $member);
        $this->assertEquals($team->getId(), $member->getTeam()->getId());
        $this->assertEquals($user->getId(), $member->getUser()->getId());
    }
}
