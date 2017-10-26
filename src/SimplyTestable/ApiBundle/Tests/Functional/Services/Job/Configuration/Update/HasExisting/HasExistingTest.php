<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Update\HasExisting;

use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Update\ServiceTest;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

abstract class HasExistingTest extends ServiceTest {

    /**
     * @var JobConfiguration
     */
    private $jobConfiguration = null;

    protected function setUp() {
        parent::setUp();

        $this->preCreateJobConfigurations();

        $websiteService = $this->container->get('simplytestable.services.websiteservice');

        $this->getJobConfigurationService()->setUser($this->getCurrentUser());

        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');
        $fullSiteJobType = $jobTypeService->getByName(JobTypeService::FULL_SITE_NAME);

        $firstValues = new ConfigurationValues();
        $firstValues->setWebsite($websiteService->fetch('http://example.com/'));
        $firstValues->setType($fullSiteJobType);
        $firstValues->setTaskConfigurationCollection($this->getStandardTaskConfigurationCollection());
        $firstValues->setLabel('first');
        $firstValues->setParameters('first-job-configuration-parameters');

        $this->jobConfiguration = $this->getJobConfigurationService()->create($firstValues);

        $secondValues = new ConfigurationValues();
        $secondValues->setWebsite($websiteService->fetch('http://example.com/'));
        $secondValues->setType($fullSiteJobType);
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