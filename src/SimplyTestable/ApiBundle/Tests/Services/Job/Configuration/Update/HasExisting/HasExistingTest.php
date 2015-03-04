<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Update\HasExisting;

use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Update\ServiceTest;

abstract class HasExistingTest extends ServiceTest {

    public function setUp() {
        parent::setUp();

        $this->preCreateJobConfigurations();

        $this->getJobConfigurationService()->setUser($this->getCurrentUser());
        $this->getJobConfigurationService()->create(
            $this->getWebSiteService()->fetch('http://example.com/'),
            $this->getJobTypeService()->getFullSiteType(),
            [],
            'first',
            'first-job-configuration-parameters'
        );

        $this->getJobConfigurationService()->create(
            $this->getWebSiteService()->fetch('http://example.com/'),
            $this->getJobTypeService()->getFullSiteType(),
            [],
            'second',
            'second-job-configuration-parameters'
        );
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
        $this->getJobConfigurationService()->update(
            'first',
            $this->getWebSiteService()->fetch('http://example.com/'),
            $this->getJobTypeService()->getFullSiteType(),
            [],
            'second-job-configuration-parameters'
        );
    }

}