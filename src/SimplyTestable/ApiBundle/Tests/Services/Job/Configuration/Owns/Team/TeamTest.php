<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Owns\Team;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Owns\ServiceTest;

abstract class TeamTest extends ServiceTest {

    /**
     * @var User
     */
    protected $leader;


    /**
     * @var User
     */
    protected $member;


    /**
     * @var bool
     */
    private $ownsResult;


    public function setUp() {
        parent::setUp();

        $this->leader = $this->createAndActivateUser('leader@example.com');
        $this->member = $this->createAndActivateUser('user@example.com');

        $this->getTeamMemberService()->add($this->getTeamService()->create(
            'Foo',
            $this->leader
        ), $this->member);

        $jobConfiguration = new JobConfiguration();
        $jobConfiguration->setUser($this->getJobConfigurationUser());

        $this->getJobConfigurationService()->setUser($this->getServiceUser());

        $this->ownsResult = $this->getJobConfigurationService()->owns($jobConfiguration);
    }


    abstract protected function getJobConfigurationUser();
    abstract protected function getServiceUser();

    public function testOwns() {
        $this->assertTrue($this->ownsResult);
    }
}
