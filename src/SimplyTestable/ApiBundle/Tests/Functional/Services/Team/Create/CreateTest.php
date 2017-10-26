<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Team\Remove;

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
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userFactory = new UserFactory($this->container);
    }

    public function testEmptyNameThrowsTeamServiceException()
    {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Team\Exception',
            '',
            TeamServiceException::CODE_NAME_EMPTY
        );

        $this->getTeamService()->create(
            '',
            $this->userFactory->createAndActivateUser()
        );
    }

    public function testTakenNameThrowsTeamServiceException()
    {
        $this->getTeamService()->create(
            'Foo',
            $this->userFactory->createAndActivateUser()
        );

        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Team\Exception',
            '',
            TeamServiceException::CODE_NAME_TAKEN
        );

        $this->getTeamService()->create(
            'Foo',
            $this->userFactory->createAndActivateUser([
                UserFactory::KEY_EMAIL => 'user2@example.com',
            ])
        );
    }

    public function testUserAlreadyLeadsTeamThrowsTeamServiceException()
    {
        $user = $this->userFactory->createAndActivateUser();

        $this->getTeamService()->create(
            'Foo',
            $user
        );

        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Team\Exception',
            '',
            TeamServiceException::USER_ALREADY_LEADS_TEAM
        );

        $this->getTeamService()->create(
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

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $user = $this->userFactory->createAndActivateUser();

        $teamMemberService->add($team, $user);

        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Team\Exception',
            '',
            TeamServiceException::USER_ALREADY_ON_TEAM
        );

        $this->getTeamService()->create(
            'Bar',
            $user
        );
    }
}
