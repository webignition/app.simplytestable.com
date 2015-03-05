<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Delete\Success;

use SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Delete\ServiceTest;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;

abstract class SuccessTest extends ServiceTest {

    const LABEL = 'bar';

    /**
     * @var JobConfiguration
     */
    private $jobConfiguration;

    abstract protected function getCurrentUser();

    public function setUp() {
        parent::setUp();

        $this->preCreateJobConfigurations();

        $this->getJobConfigurationService()->setUser($this->getCurrentUser());
        $this->jobConfiguration = $this->getJobConfigurationService()->create(
            $this->getWebSiteService()->fetch('http://original.example.com/'),
            $this->getJobTypeService()->getFullSiteType(),
            $this->getStandardTaskConfigurationCollection(),
            self::LABEL,
            ''
        );

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