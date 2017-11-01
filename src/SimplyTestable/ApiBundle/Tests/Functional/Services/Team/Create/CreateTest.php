<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Team\Remove;

use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\Team\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\Team\Exception as TeamServiceException;

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

    public function testEmptyNameThrowsTeamServiceException()
    {
        $this->expectException(TeamServiceException::class);
        $this->expectExceptionCode(TeamServiceException::CODE_NAME_EMPTY);

        $this->teamService->create(
            '',
            $this->userFactory->createAndActivateUser()
        );
    }

    public function testTakenNameThrowsTeamServiceException()
    {
        $this->teamService->create(
            'Foo',
            $this->userFactory->createAndActivateUser()
        );

        $this->expectException(TeamServiceException::class);
        $this->expectExceptionCode(TeamServiceException::CODE_NAME_TAKEN);

        $this->teamService->create(
            'Foo',
            $this->userFactory->createAndActivateUser([
                UserFactory::KEY_EMAIL => 'user2@example.com',
            ])
        );
    }

    public function testUserAlreadyLeadsTeamThrowsTeamServiceException()
    {
        $user = $this->userFactory->createAndActivateUser();

        $this->teamService->create(
            'Foo',
            $user
        );

        $this->expectException(TeamServiceException::class);
        $this->expectExceptionCode(TeamServiceException::USER_ALREADY_LEADS_TEAM);

        $this->teamService->create(
            'Bar',
            $user
        );
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

        $this->expectException(TeamServiceException::class);
        $this->expectExceptionCode(TeamServiceException::USER_ALREADY_ON_TEAM);

        $this->teamService->create(
            'Bar',
            $user
        );
    }
}
