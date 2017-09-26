<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Team\TeamInvite\GetForTeam;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\TeamInvite\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\TeamInvite\Exception as TeamInviteServiceException;

class GetForTeamTest extends ServiceTest
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

    public function testTeamWithNoInvitesReturnsEmptyCollection()
    {
        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);

        $team = $this->getTeamService()->create(
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

        $team = $this->getTeamService()->create(
            'Foo1',
            $leader
        );

        $invite = $this->getTeamInviteService()->get($leader, $user);

        $this->assertEquals([$invite], $this->getTeamInviteService()->getForTeam($team));
    }
}
