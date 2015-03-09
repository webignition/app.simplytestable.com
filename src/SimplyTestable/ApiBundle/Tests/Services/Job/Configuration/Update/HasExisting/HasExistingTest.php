<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Update\HasExisting;

use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Update\ServiceTest;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

abstract class HasExistingTest extends ServiceTest {

    /**
     * @var JobConfiguration
     */
    private $jobConfiguration = null;

    public function setUp() {
        parent::setUp();

        $this->preCreateJobConfigurations();

        $this->getJobConfigurationService()->setUser($this->getCurrentUser());

        $firstValues = new ConfigurationValues();
        $firstValues->setWebsite($this->getWebSiteService()->fetch('http://example.com/'));
        $firstValues->setType($this->getJobTypeService()->getFullSiteType());
        $firstValues->setTaskConfigurationCollection($this->getStandardTaskConfigurationCollection());
        $firstValues->setLabel('first');
        $firstValues->setParameters('first-job-configuration-parameters');

        $this->jobConfiguration = $this->getJobConfigurationService()->create($firstValues);

        $secondValues = new ConfigurationValues();
        $secondValues->setWebsite($this->getWebSiteService()->fetch('http://example.com/'));
        $secondValues->setType($this->getJobTypeService()->getFullSiteType());
        $secondValues->setTaskConfigurationCollection($this->getStandardTaskConfigurationCollection());
        $secondValues->setLabel('second');
        $secondValues->setParameters('second-job-configuration-parameters');

        $this->getJobConfigurationService()->create($secondValues);
    }

    protected function preCreateJobConfigurations() {

    }

    abstract protected function getCurrentUser();

    public function testHasExistingThrowsException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception',
            'Matching configuration already exists',
            JobConfigurationServiceException::CODE_CONFIGURATION_ALREADY_EXISTS
        );

        $this->getJobConfigurationService()->setUser($this->getCurrentUser());

        $newValues = new ConfigurationValues();
        $newValues->setParameters('second-job-configuration-parameters');

        $this->getJobConfigurationService()->update(
            $this->jobConfiguration,
            $newValues
        );
    }

}