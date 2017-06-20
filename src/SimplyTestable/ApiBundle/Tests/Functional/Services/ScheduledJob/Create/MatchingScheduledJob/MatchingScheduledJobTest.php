<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\Create\MatchingScheduledJob;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\Create\ServiceTest;

abstract class MatchingScheduledJobTest extends ServiceTest {

    /**
     * @var JobConfiguration
     */
    protected $jobConfiguration;

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
            $this->getFirstCronModifier(),
            true
        );
    }

    abstract protected function getFirstCronModifier();

}