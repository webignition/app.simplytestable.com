<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Team\TeamInvite\GetForUser;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\TeamInvite\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\TeamInvite\Exception as TeamInviteServiceException;

class GetForUserTest extends ServiceTest
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

    public function testNoInvitesReturnsEmptyCollection()
    {
        $user = $this->userFactory->createAndActivateUser();
        $this->assertEquals([], $this->getTeamInviteService()->getForUser($user));
    }


    public function testWithSingleInvite()
    {
        $leader = $this->userFactory->createAndActivateUser('leader@example.com');
        $user = $this->userFactory->createAndActivateUser();

        $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $invite = $this->getTeamInviteService()->get(
            $leader,
            $user
        );

        $invites = $this->getTeamInviteService()->getForUser($user);

        $this->assertEquals(1, count($invites));
        $this->assertEquals($invite->getId(), $invites[0]->getId());
    }

    public function testWithManyInvites()
    {
        $leader1 = $this->userFactory->createAndActivateUser('leader1@example.com');
        $leader2 = $this->userFactory->createAndActivateUser('leader2@example.com');
        $user = $this->userFactory->createAndActivateUser();

        $this->getTeamService()->create(
            'Foo1',
            $leader1
        );

        $this->getTeamService()->create(
            'Foo2',
            $leader2
        );

        $invite1 = $this->getTeamInviteService()->get(
            $leader1,
            $user
        );

        $invite2 = $this->getTeamInviteService()->get(
            $leader2,
            $user
        );

        $this->assertFalse($invite1->getId() == $invite2->getId());

        $invites = $this->getTeamInviteService()->getForUser($user);

        $this->assertEquals(2, count($invites));
        $this->assertEquals($invite1->getId(), $invites[0]->getId());
        $this->assertEquals($invite2->getId(), $invites[1]->getId());
    }
}
