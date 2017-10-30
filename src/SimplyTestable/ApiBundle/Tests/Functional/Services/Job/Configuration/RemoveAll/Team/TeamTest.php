<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\RemoveAll\Team;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\RemoveAll\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;

abstract class TeamTest extends ServiceTest {

    const LABEL = 'bar';

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


    protected function setUp() {
        parent::setUp();

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

        $this->setUser($this->getCurrentUser());
    }

    abstract protected function getCurrentUser();

    public function testCallWithoutSettingUserThrowsException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception',
            'Unable to remove all; user is in a team',
            JobConfigurationServiceException::CODE_UNABLE_TO_PERFORM_AS_USER_IS_IN_A_TEAM
        );

        $this->getJobConfigurationService()->removeAll();
    }

}