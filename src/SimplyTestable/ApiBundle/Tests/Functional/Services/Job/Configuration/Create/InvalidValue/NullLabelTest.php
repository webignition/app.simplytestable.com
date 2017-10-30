<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Create\InvalidValue;

use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Create\ServiceTest;

class NullLabelTest extends ServiceTest {

    public function testCallWithoutSettingUserThrowsException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception',
            'Label cannot be empty',
            JobConfigurationServiceException::CODE_LABEL_CANNOT_BE_EMPTY
        );

        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');
        $websiteService = $this->container->get('simplytestable.services.websiteservice');
        $userService = $this->container->get('simplytestable.services.userservice');

        $fullSiteJobType = $jobTypeService->getByName(JobTypeService::FULL_SITE_NAME);

        $values = new ConfigurationValues();
        $values->setLabel(null);
        $values->setTaskConfigurationCollection($this->getStandardTaskConfigurationCollection());
        $values->setType($fullSiteJobType);
        $values->setWebsite($websiteService->fetch('http://example.com/'));

        $this->setUser($userService->getPublicUser());
        $this->getJobConfigurationService()->create($values);
    }

}