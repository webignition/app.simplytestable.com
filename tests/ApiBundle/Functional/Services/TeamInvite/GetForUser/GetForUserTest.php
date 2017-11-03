<?php

namespace Tests\ApiBundle\Functional\Services\Team\TeamInvite\GetForUser;

use SimplyTestable\ApiBundle\Services\Team\InviteService;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\Services\TeamInvite\ServiceTest;

class GetForUserTest extends ServiceTest
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

    public function testNoInvitesReturnsEmptyCollection()
    {
        $user = $this->userFactory->createAndActivateUser();
        $this->assertEquals([], $this->teamInviteService->getForUser($user));
    }


    public function testWithSingleInvite()
    {
        $leader = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $user = $this->userFactory->createAndActivateUser();

        $this->teamService->create(
            'Foo',
            $leader
        );

        $invite = $this->teamInviteService->get(
            $leader,
            $user
        );

        $invites = $this->teamInviteService->getForUser($user);

        $this->assertEquals(1, count($invites));
        $this->assertEquals($invite->getId(), $invites[0]->getId());
    }

    public function testWithManyInvites()
    {
        $leader1 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader1@example.com',
        ]);
        $leader2 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader2@example.com',
        ]);
        $user = $this->userFactory->createAndActivateUser();

        $this->teamService->create(
            'Foo1',
            $leader1
        );

        $this->teamService->create(
            'Foo2',
            $leader2
        );

        $invite1 = $this->teamInviteService->get(
            $leader1,
            $user
        );

        $invite2 = $this->teamInviteService->get(
            $leader2,
            $user
        );

        $this->assertFalse($invite1->getId() == $invite2->getId());

        $invites = $this->teamInviteService->getForUser($user);

        $this->assertEquals(2, count($invites));
        $this->assertEquals($invite1->getId(), $invites[0]->getId());
        $this->assertEquals($invite2->getId(), $invites[1]->getId());
    }
}
