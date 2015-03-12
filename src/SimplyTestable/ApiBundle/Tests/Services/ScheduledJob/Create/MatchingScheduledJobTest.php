<?php

namespace SimplyTestable\ApiBundle\Tests\Services\ScheduledJob\Create;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Exception\Services\ScheduledJob\Exception as ScheduledJobServiceException;

class MatchingScheduledJobTest extends ServiceTest {

    /**
     * @var JobConfiguration
     */
    private $jobConfiguration;

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

        $this->getScheduledJobService()->create(
            $this->jobConfiguration,
            '* * * * *',
            true
        );
    }

    public function testCreateMatchingScheduledJobThrowsException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\ScheduledJob\Exception',
            'Matching scheduled job exists',
            ScheduledJobServiceException::CODE_MATCHING_SCHEDULED_JOB_EXISTS
        );

        $this->getScheduledJobService()->create(
            $this->jobConfiguration,
            '* * * * *',
            true
        );
    }

}