<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Delete\Success\Team;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Delete\Success\SuccessTest;

abstract class TeamTest extends SuccessTest
{
    /**
     * @var User
     */
    protected $leader;


    /**
     * @var User
     */
    protected $member1;

    /**
     * @var User
     */
    protected $member2;


    public function preCreateJobConfigurations()
    {
        $teamMemberService = $this->container->get('simplytestable.services.teammemberservice');
        $teamService = $this->container->get('simplytestable.services.teamservice');

        $userFactory = new UserFactory($this->container);

        $this->leader = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $this->member1 = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'user1@example.com',
        ]);
        $this->member2 = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'user2@example.com',
        ]);

        $team = $teamService->create(
            'Foo',
            $this->leader
        );

        $teamMemberService->add($team, $this->member1);
        $teamMemberService->add($team, $this->member2);
    }

    protected function getCurrentUser()
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        return $userService->getPublicUser();
    }
}
