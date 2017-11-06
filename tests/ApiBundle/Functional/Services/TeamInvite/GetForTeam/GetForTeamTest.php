<?php

namespace Tests\ApiBundle\Functional\Services\Team\TeamInvite\GetForTeam;

use SimplyTestable\ApiBundle\Services\Team\InviteService;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\Services\TeamInvite\ServiceTest;

class GetForTeamTest extends ServiceTest
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

    public function testTeamWithNoInvitesReturnsEmptyCollection()
    {
        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);

        $team = $this->teamService->create(
            'Foo1',
            $leader
        );

        $this->assertEquals([], $this->teamInviteService->getForTeam($team));
    }

    public function testTeamWithInvitesReturnsInvites()
    {
        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $user = $this->userFactory->createAndActivateUser();

        $team = $this->teamService->create(
            'Foo1',
            $leader
        );

        $invite = $this->teamInviteService->get($leader, $user);

        $this->assertEquals([$invite], $this->teamInviteService->getForTeam($team));
    }
}