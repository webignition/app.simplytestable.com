<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Create\NonUniqueLabel;

use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Create\ServiceTest;
use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;

class ForUserTest extends ServiceTest {

    const LABEL = 'foo';

    /**
     * @var ConfigurationValues
     */
    private $values;

    protected function setUp() {
        parent::setUp();

        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');
        $fullSiteJobType = $jobTypeService->getByName(JobTypeService::FULL_SITE_NAME);

        $this->values = new ConfigurationValues();
        $this->values->setLabel(self::LABEL);
        $this->values->setTaskConfigurationCollection($this->getStandardTaskConfigurationCollection());
        $this->values->setType($fullSiteJobType);
        $this->values->setWebsite($this->getWebSiteService()->fetch('http://example.com/'));

        $userService = $this->container->get('simplytestable.services.userservice');
        $this->getJobConfigurationService()->setUser($userService->getPublicUser());
        $this->getJobConfigurationService()->create($this->values);
    }

    public function testCreateWithNonUniqueLabelForUserThrowsException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception',
            'Label "' . self::LABEL . '" is not unique',
            JobConfigurationServiceException::CODE_LABEL_NOT_UNIQUE
        );

        $this->getJobConfigurationService()->create($this->values);
    }

}
