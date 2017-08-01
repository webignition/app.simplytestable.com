<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Delete\Success;

use SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Delete\ServiceTest;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;

abstract class SuccessTest extends ServiceTest {

    const LABEL = 'bar';

    /**
     * @var JobConfiguration
     */
    private $jobConfiguration;

    abstract protected function getCurrentUser();

    protected function setUp() {
        parent::setUp();

        $this->preCreateJobConfigurations();

        $jobConfigurationValues = new ConfigurationValues();
        $jobConfigurationValues->setLabel(self::LABEL);
        $jobConfigurationValues->setTaskConfigurationCollection($this->getStandardTaskConfigurationCollection());
        $jobConfigurationValues->setType($this->getJobTypeService()->getFullSiteType());
        $jobConfigurationValues->setWebsite($this->getWebSiteService()->fetch('http://original.example.com/'));

        $this->getJobConfigurationService()->setUser($this->getCurrentUser());
        $this->jobConfiguration = $this->getJobConfigurationService()->create($jobConfigurationValues);

        $this->assertNotNull($this->jobConfiguration->getId());
        $this->getJobConfigurationService()->delete(self::LABEL);
    }

    protected function preCreateJobConfigurations() {
    }

    public function testJobConfigurationIdIsNotSet() {
        $this->assertNull($this->jobConfiguration->getId());
    }

    public function testCannotBeRetrievedWithLabel() {
        $this->assertNull($this->getJobConfigurationService()->get(self::LABEL));
    }

}