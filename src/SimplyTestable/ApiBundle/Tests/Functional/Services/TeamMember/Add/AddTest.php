<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\TeamMember\Add;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\TeamMember\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\TeamMember\Exception as TeamMemberServiceException;

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

    public function testUserAlreadyOnTeamThrowsTeamMemberServiceException()
    {
        $leader = $this->userFactory->createAndActivateUser('leader@example.com');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $user = $this->userFactory->createAndActivateUser();

        $this->getTeamMemberService()->add($team, $user);

        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\TeamMember\Exception',
            '',
            TeamMemberServiceException::USER_ALREADY_ON_TEAM
        );

        $this->getTeamMemberService()->add($team, $user);
    }

    public function testUserNotAlreadyOnTeamIsAdded()
    {
        $leader = $this->userFactory->createAndActivateUser('leader@example.com');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $user = $this->userFactory->createAndActivateUser();

        $member = $this->getTeamMemberService()->add($team, $user);

        $this->assertInstanceOf('SimplyTestable\ApiBundle\Entity\Team\Member', $member);
        $this->assertEquals($team->getId(), $member->getTeam()->getId());
        $this->assertEquals($user->getId(), $member->getUser()->getId());
    }
}
