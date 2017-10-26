<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Create\InvalidValue;

use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Create\ServiceTest;

class EmptyTaskConfigurationCollectionTest extends ServiceTest {

    public function testCallWithoutSettingUserThrowsException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception',
            'TaskConfigurationCollection is empty',
            JobConfigurationServiceException::CODE_TASK_CONFIGURATION_COLLECTION_IS_EMPTY
        );

        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');
        $websiteService = $this->container->get('simplytestable.services.websiteservice');
        $userService = $this->container->get('simplytestable.services.userservice');

        $fullSiteJobType = $jobTypeService->getByName(JobTypeService::FULL_SITE_NAME);

        $values = new ConfigurationValues();
        $values->setLabel('foo');
        $values->setType($fullSiteJobType);
        $values->setWebsite($websiteService->fetch('http://example.com/'));

        $this->getJobConfigurationService()->setUser($userService->getPublicUser());
        $this->getJobConfigurationService()->create($values);
    }

}