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
        $teamMemberService = $this->container->get('simplytestable.services.teammemberservice');

        $user = $this->userFactory->create();

        $this->assertTrue($teamMemberService>remove($user));
    }

    public function testRemoveUserThatIsOnATeamReturnsTrue()
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

        $this->assertTrue($teamMemberService->remove($user));
    }

    public function testRemoveUserThatIsOnATeamRemovesTheUser()
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

        $this->assertTrue($teamMemberService->contains($team, $user));

        $teamMemberService->remove($user);

        $this->assertFalse($teamMemberService->contains($team, $user));
    }
}
