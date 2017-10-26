<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Team\TeamInvite\HasForTeamAndUser;

use SimplyTestable\ApiBundle\Services\Team\InviteService;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\TeamInvite\ServiceTest;

class HasForTeamAndUserTest extends ServiceTest
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
        $this->assertFalse($this->teamInviteService->hasForTeamAndUser($team, $user));
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

        $this->teamInviteService->get(
            $leader,
            $user
        );

        $this->assertTrue($this->teamInviteService->hasForTeamAndUser($team, $user));
    }
}
