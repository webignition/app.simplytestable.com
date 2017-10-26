<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Team\TeamInvite\GetForTeamAndUser;

use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\TeamInvite\ServiceTest;

class GetForTeamAndUserTest extends ServiceTest
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

    public function testReturnsNullIfNoInvite()
    {
        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $team = $this->teamService->create(
            'Foo1',
            $leader
        );

        $user = $this->userFactory->createAndActivateUser();
        $this->assertNull($this->getTeamInviteService()->getForTeamAndUser($team, $user));
    }

    public function testReturnsInvite()
    {
        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $user = $this->userFactory->createAndActivateUser();

        $team = $this->teamService->create(
            'Foo1',
            $leader
        );

        $this->getTeamInviteService()->get(
            $leader,
            $user
        );

        $invite = $this->getTeamInviteService()->getForTeamAndUser($team, $user);

        $this->assertNotNull($invite->getId());
        $this->assertEquals($team->getId(), $invite->getTeam()->getId());
        $this->assertEquals($user->getId(), $invite->getUser()->getId());
    }
}
