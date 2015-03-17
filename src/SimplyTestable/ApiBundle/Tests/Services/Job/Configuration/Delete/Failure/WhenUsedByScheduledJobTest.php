<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Delete\Failure;

use SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Delete\ServiceTest;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;
use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;

class WhenUsedByScheduledJobTest extends ServiceTest {

    const LABEL = 'bar';

    /**
     * @var JobConfiguration
     */
    private $jobConfiguration;

    public function setUp() {
        parent::setUp();

        $user = $this->createAndActivateUser();

        $jobConfigurationValues = new ConfigurationValues();
        $jobConfigurationValues->setLabel(self::LABEL);
        $jobConfigurationValues->setTaskConfigurationCollection($this->getStandardTaskConfigurationCollection());
        $jobConfigurationValues->setType($this->getJobTypeService()->getFullSiteType());
        $jobConfigurationValues->setWebsite($this->getWebSiteService()->fetch('http://original.example.com/'));

        $this->getJobConfigurationService()->setUser($user);
        $this->jobConfiguration = $this->getJobConfigurationService()->create($jobConfigurationValues);

        $this->assertNotNull($this->jobConfiguration->getId());

        $this->getScheduledJobService()->create($this->jobConfiguration);
    }

    public function testDeleteThrowsForeignKeyException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception',
            'Job configuration is in use by one or more scheduled jobs',
            JobConfigurationServiceException::CODE_IS_IN_USE_BY_SCHEDULED_JOB
        );

        $this->getJobConfigurationService()->delete(self::LABEL);
    }

}