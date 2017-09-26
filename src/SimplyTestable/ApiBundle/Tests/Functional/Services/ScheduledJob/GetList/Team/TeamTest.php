<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\GetList\Team;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\GetList\ServiceTest;
use SimplyTestable\ApiBundle\Entity\ScheduledJob;

abstract class TeamTest extends ServiceTest {

    /**
     * @var ScheduledJob[]
     */
    private $list;


    /**
     * @var User
     */
    protected $leader;


    /**
     * @var User
     */
    protected $member;


    protected function setUp() {
        parent::setUp();

        $userFactory = new UserFactory($this->container);

        $this->leader = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $this->member = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'member@example.com',
        ]);

        $this->getTeamMemberService()->add($this->getTeamService()->create(
            'Foo',
            $this->leader
        ), $this->member);

        $jobConfiguration = $this->createJobConfiguration([
            'label' => 'foo',
            'parameters' => 'parameters',
            'type' => 'Full site',
            'website' => 'http://example.com/',
            'task_configuration' => [
                'HTML validation' => []
            ],

        ], $this->leader);

        $this->getJobConfigurationService()->setUser($this->leader);
        $this->getScheduledJobService()->create(
            $jobConfiguration,
            '* * * * *',
            null,
            true
        );

        $this->getJobConfigurationService()->setUser($this->member);
        $this->getScheduledJobService()->create(
            $jobConfiguration,
            '* * * * 0',
            null,
            true
        );

        $this->getScheduledJobService()->setUser($this->getServiceRequestUser());
        $this->list = $this->getScheduledJobService()->getList();
    }

    abstract protected function getServiceRequestUser();


    public function testListSize() {
        $this->assertEquals(2, count($this->list));
    }

    public function testListContainsOnlyScheduledJobs() {
        foreach ($this->list as $listItem) {
            $this->assertInstanceOf('SimplyTestable\ApiBundle\Entity\ScheduledJob', $listItem);
        }
    }

}