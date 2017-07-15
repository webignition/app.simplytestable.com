<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\RemoveAll\User;

use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;

class InUseByScheduledJobTest extends UserTest {

    public function setUp() {
        parent::setUp();

        $this->getScheduledJobService()->create($this->jobConfiguration);
    }

    public function testRemoveThrowsException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception',
            'One or more job configurations are in use by one or more scheduled jobs',
            JobConfigurationServiceException::CODE_IS_IN_USE_BY_SCHEDULED_JOB
        );

        $this->getJobConfigurationService()->removeAll();
    }
}