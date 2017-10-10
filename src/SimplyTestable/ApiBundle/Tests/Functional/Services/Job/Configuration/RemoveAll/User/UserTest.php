<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\RemoveAll\User;

use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\RemoveAll\ServiceTest;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;

abstract class UserTest extends ServiceTest {

    const LABEL = 'bar';

    /**
     * @var JobConfiguration
     */
    protected $jobConfiguration;


    protected function setUp() {
        parent::setUp();

        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');
        $fullSiteJobType = $jobTypeService->getByName(JobTypeService::FULL_SITE_NAME);

        $this->preCreateJobConfigurations();

        $jobConfigurationValues = new ConfigurationValues();
        $jobConfigurationValues->setLabel(self::LABEL);
        $jobConfigurationValues->setTaskConfigurationCollection($this->getStandardTaskConfigurationCollection());
        $jobConfigurationValues->setType($fullSiteJobType);
        $jobConfigurationValues->setWebsite($this->getWebSiteService()->fetch('http://original.example.com/'));

        $userService = $this->container->get('simplytestable.services.userservice');
        $this->getJobConfigurationService()->setUser($userService->getPublicUser());
        $this->jobConfiguration = $this->getJobConfigurationService()->create($jobConfigurationValues);

        $this->assertNotNull($this->jobConfiguration->getId());
        $this->assertEquals(1, $this->jobConfiguration->getTaskConfigurationsAsCollection()->count());
    }

    protected function preCreateJobConfigurations() {
    }
}