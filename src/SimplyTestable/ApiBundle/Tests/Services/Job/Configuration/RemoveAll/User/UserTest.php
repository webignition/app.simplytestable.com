<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\RemoveAll\User;

use SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\RemoveAll\ServiceTest;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;

class UserTest extends ServiceTest {

    const LABEL = 'bar';

    /**
     * @var JobConfiguration
     */
    private $jobConfiguration;


    public function setUp() {
        parent::setUp();

        $this->preCreateJobConfigurations();

        $jobConfigurationValues = new ConfigurationValues();
        $jobConfigurationValues->setLabel(self::LABEL);
        $jobConfigurationValues->setTaskConfigurationCollection($this->getStandardTaskConfigurationCollection());
        $jobConfigurationValues->setType($this->getJobTypeService()->getFullSiteType());
        $jobConfigurationValues->setWebsite($this->getWebSiteService()->fetch('http://original.example.com/'));


        $this->getJobConfigurationService()->setUser($this->getUserService()->getPublicUser());
        $this->jobConfiguration = $this->getJobConfigurationService()->create($jobConfigurationValues);

        $this->assertNotNull($this->jobConfiguration->getId());
        $this->assertEquals(1, $this->jobConfiguration->getTaskConfigurationsAsCollection()->count());
        $this->getJobConfigurationService()->removeAll();
    }

    protected function preCreateJobConfigurations() {
    }

    public function testJobConfigurationIdIsNotSet() {
        $this->assertNull($this->jobConfiguration->getId());
    }

    public function testCannotBeRetrievedWithLabel() {
        $this->assertNull($this->getJobConfigurationService()->get(self::LABEL));
    }

    public function testJobTaskConfigurationsAreRemoved() {
        $this->assertEquals(0, $this->jobConfiguration->getTaskConfigurationsAsCollection()->count());
    }
}