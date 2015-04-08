<?php

namespace SimplyTestable\ApiBundle\Tests\Services\ScheduledJob\Update;

use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

class NoUpdatedValuesTest extends ServiceTest {

    /**
     * @var ScheduledJob
     */
    private $scheduledJob;


    /**
     * @var JobConfiguration
     */
    private $jobConfiguration;


    /**
     * @var string
     */
    private $schedule = '* * * * *';


    /**
     * @var bool
     */
    private $isRecurring = true;


    public function setUp() {
        parent::setUp();

        $user = $this->createAndActivateUser('user@example.com');

        $this->jobConfiguration = $this->createJobConfiguration([
            'label' => 'foo',
            'parameters' => 'parameters',
            'type' => 'Full site',
            'website' => 'http://example.com/',
            'task_configuration' => [
                'HTML validation' => []
            ],

        ], $user);

        $this->scheduledJob = $this->getScheduledJobService()->create(
            $this->jobConfiguration,
            $this->schedule,
            null,
            $this->isRecurring
        );

        $this->getScheduledJobService()->update($this->scheduledJob, $this->jobConfiguration, $this->schedule, null, $this->isRecurring);
    }


    public function testPropertiesAreUnchanged() {
        $this->assertEquals($this->jobConfiguration->getId(), $this->scheduledJob->getJobConfiguration()->getId());
        $this->assertEquals($this->schedule, $this->scheduledJob->getCronJob()->getSchedule());
        $this->assertEquals($this->isRecurring, $this->scheduledJob->getIsRecurring());
    }

}