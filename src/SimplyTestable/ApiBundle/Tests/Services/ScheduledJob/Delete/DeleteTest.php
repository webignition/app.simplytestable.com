<?php

namespace SimplyTestable\ApiBundle\Tests\Services\ScheduledJob\Delete;

use SimplyTestable\ApiBundle\Entity\ScheduledJob;

class SingleUserTest extends ServiceTest {

    /**
     * @var ScheduledJob
     */
    private $scheduledJob;

    public function setUp() {
        parent::setUp();

        $user = $this->createAndActivateUser('user@example.com');

        $jobConfiguration = $this->createJobConfiguration([
            'label' => 'foo',
            'parameters' => 'parameters',
            'type' => 'Full site',
            'website' => 'http://example.com/',
            'task_configuration' => [
                'HTML validation' => []
            ],

        ], $user);

        $this->scheduledJob = $this->getScheduledJobService()->create(
            $jobConfiguration,
            '* * * * *',
            null,
            true
        );

        $this->getScheduledJobService()->delete($this->scheduledJob);
    }


    public function testCronJobIsDeleted() {
        $this->assertNull($this->scheduledJob->getCronJob()->getId());
    }


    public function testScheduledJobIsDeleted() {
        $this->assertNull($this->scheduledJob->getId());
    }


    public function testJobConfigurationIsNotDeleted() {
        $this->assertNotNull($this->scheduledJob->getJobConfiguration()->getId());
    }
}