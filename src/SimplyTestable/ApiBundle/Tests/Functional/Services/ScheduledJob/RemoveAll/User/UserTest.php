<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\RemoveAll\User;

use SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\RemoveAll\ServiceTest;
use SimplyTestable\ApiBundle\Entity\ScheduledJob;

abstract class UserTest extends ServiceTest {

    const LABEL = 'bar';

    /**
     * @var ScheduledJob
     */
    protected $scheduledJob;


    public function setUp() {
        parent::setUp();

        $user = $this->createAndActivateUser();

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
}