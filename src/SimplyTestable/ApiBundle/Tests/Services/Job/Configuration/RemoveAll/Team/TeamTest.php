<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\RemoveAll\Team;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\RemoveAll\ServiceTest;
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


    public function setUp() {
        parent::setUp();

        $this->leader = $this->createAndActivateUser('leader@example.com', 'password');
        $this->member1 = $this->createAndActivateUser('user1@example.com');
        $this->member2 = $this->createAndActivateUser('user2@example.com');

        $team = $this->getTeamService()->create(
            'Foo',
            $this->leader
        );

        $this->getTeamMemberService()->add($team, $this->member1);
        $this->getTeamMemberService()->add($team, $this->member2);

        $this->getJobConfigurationService()->setUser($this->getCurrentUser());
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