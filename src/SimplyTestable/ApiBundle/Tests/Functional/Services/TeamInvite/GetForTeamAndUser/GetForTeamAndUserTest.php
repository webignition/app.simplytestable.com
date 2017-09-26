<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Team\TeamInvite\GetForTeamAndUser;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\TeamInvite\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\TeamInvite\Exception as TeamInviteServiceException;

class GetForTeamAndUserTest extends ServiceTest
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
        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $team = $this->getTeamService()->create(
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

        $team = $this->getTeamService()->create(
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
