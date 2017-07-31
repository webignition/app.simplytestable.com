<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\TeamMember\Add;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\TeamMember\ServiceTest;

class RemoveTest extends ServiceTest
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

    public function testRemoveUserThatIsNotOnATeamReturnsTrue()
    {
        $user = $this->userFactory->create();

        $this->assertTrue($this->getTeamMemberService()->remove($user));
    }

    public function testRemoveUserThatIsOnATeamReturnsTrue()
    {
        $leader = $this->userFactory->createAndActivateUser('leader@example.com');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $user = $this->userFactory->createAndActivateUser();

        $this->getTeamMemberService()->add($team, $user);

        $this->assertTrue($this->getTeamMemberService()->remove($user));
    }

    public function testRemoveUserThatIsOnATeamRemovesTheUser()
    {
        $leader = $this->userFactory->createAndActivateUser('leader@example.com');

        $team = $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $user = $this->userFactory->createAndActivateUser();

        $this->getTeamMemberService()->add($team, $user);

        $this->assertTrue($this->getTeamMemberService()->contains($team, $user));

        $this->getTeamMemberService()->remove($user);

        $this->assertFalse($this->getTeamMemberService()->contains($team, $user));
    }
}
