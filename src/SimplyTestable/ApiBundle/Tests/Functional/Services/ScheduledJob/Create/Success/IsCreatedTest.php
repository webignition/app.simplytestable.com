<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\Create\Success;

use SimplyTestable\ApiBundle\Entity\ScheduledJob;

class TestTest extends SuccessTest {

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
    }

    public function testIsCreated() {
        $this->assertInstanceOf('SimplyTestable\ApiBundle\Entity\ScheduledJob', $this->scheduledJob);
        $this->assertNotNull($this->scheduledJob->getId());
        $this->assertNotNull($this->scheduledJob->getCronJob()->getId());
        $this->assertEquals('simplytestable:scheduledjob:enqueue ' . $this->scheduledJob->getId(), $this->scheduledJob->getCronJob()->getCommand());
    }



}