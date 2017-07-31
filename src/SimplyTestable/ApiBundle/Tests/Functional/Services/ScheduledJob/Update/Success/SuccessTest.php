<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\Update\Success;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\Update\ServiceTest;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Entity\User;

abstract class SuccessTest extends ServiceTest {

    /**
     * @var JobConfiguration
     */
    private $jobConfiguration1;


    /**
     * @var JobConfiguration
     */
    private $jobConfiguration2;


    /**
     * @var ScheduledJob
     */
    private $scheduledJob;


    /**
     * @var User
     */
    protected $user;

    public function setUp() {
        parent::setUp();

        $userFactory = new UserFactory($this->container);

        $this->user = $userFactory->createAndActivateUser();

        $this->scheduledJob = $this->getScheduledJobService()->create(
            $this->getOriginalJobConfiguration(),
            $this->getOriginalSchedule(),
            null,
            $this->getOriginalIsRecurring()
        );

        $this->getScheduledJobService()->update(
            $this->scheduledJob,
            $this->getUpdatedJobConfiguration(),
            $this->getUpdatedSchedule(),
            null,
            $this->getUpdatedIsRecurring()
        );

    }

    /**
     * @return JobConfiguration
     */
    abstract protected function getOriginalJobConfiguration();

    /**
     * @return JobConfiguration
     */
    abstract protected function getUpdatedJobConfiguration();

    /**
     * @return string
     */
    abstract protected function getOriginalSchedule();

    /**
     * @return string
     */
    abstract protected function getUpdatedSchedule();

    /**
     * @return bool
     */
    abstract protected function getOriginalIsRecurring();

    /**
     * @return bool
     */
    abstract protected function getUpdatedIsRecurring();


    public function testUpdatedJobConfiguration() {
        $this->assertEquals($this->getUpdatedJobConfiguration()->getId(), $this->scheduledJob->getJobConfiguration()->getId());
    }

    public function testUpdatedSchedule() {
        $this->assertEquals($this->getUpdatedSchedule(), $this->scheduledJob->getCronJob()->getSchedule());
    }

    public function testUpdatedIsRecurring() {
        $this->assertEquals($this->getUpdatedIsRecurring(), $this->scheduledJob->getIsRecurring());
    }

}