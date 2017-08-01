<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\Get;

use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\ServiceTest as BaseServiceTest;

abstract class WithTest extends BaseServiceTest {

    /**
     * @var ScheduledJob
     */
    protected $scheduledJob;

    protected function setUp() {
        parent::setUp();

        $this->setUpPreCreate();

        $jobConfiguration = $this->createJobConfiguration([
            'label' => 'foo',
            'parameters' => 'parameters',
            'type' => 'Full site',
            'website' => 'http://example.com/',
            'task_configuration' => [
                'HTML validation' => []
            ],

        ], $this->getJobConfigurationOwner());

        $scheduledJob = $this->getScheduledJobService()->create(
            $jobConfiguration,
            '* * * * *',
            null,
            true
        );

        $this->getScheduledJobService()->setUser($this->getServiceRequestUser());
        $this->scheduledJob = $this->getScheduledJobService()->get($scheduledJob->getId());
    }

    abstract protected function getJobConfigurationOwner();
    abstract protected function getServiceRequestUser();

    protected function setUpPreCreate() {}

}
