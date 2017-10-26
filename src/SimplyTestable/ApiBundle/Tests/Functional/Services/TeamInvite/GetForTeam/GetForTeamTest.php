<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Team\TeamInvite\GetForTeam;

use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\TeamInvite\ServiceTest;

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
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userFactory = new UserFactory($this->container);
        $this->teamService = $this->container->get('simplytestable.services.teamservice');
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

        $this->assertEquals([], $this->getTeamInviteService()->getForTeam($team));
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

        $invite = $this->getTeamInviteService()->get($leader, $user);

        $this->assertEquals([$invite], $this->getTeamInviteService()->getForTeam($team));
    }
}
