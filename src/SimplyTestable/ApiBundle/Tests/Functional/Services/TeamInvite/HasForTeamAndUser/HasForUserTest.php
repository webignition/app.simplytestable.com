<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Team\TeamInvite\HasForTeamAndUser;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\TeamInvite\ServiceTest;

class HasForTeamAndUserTest extends ServiceTest
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

    public function testReturnsNullIfNoInvite()
    {
        $leader = $this->userFactory->createAndActivateUser('leader@example.com');
        $team = $this->getTeamService()->create(
            'Foo1',
            $leader
        );

        $user = $this->userFactory->createAndActivateUser();
        $this->assertFalse($this->getTeamInviteService()->hasForTeamAndUser($team, $user));
    }

    public function testReturnsInvite()
    {
        $leader = $this->userFactory->createAndActivateUser('leader@example.com');
        $user = $this->userFactory->createAndActivateUser();

        $team = $this->getTeamService()->create(
            'Foo1',
            $leader
        );

        $this->getTeamInviteService()->get(
            $leader,
            $user
        );

        $this->assertTrue($this->getTeamInviteService()->hasForTeamAndUser($team, $user));
    }
}
