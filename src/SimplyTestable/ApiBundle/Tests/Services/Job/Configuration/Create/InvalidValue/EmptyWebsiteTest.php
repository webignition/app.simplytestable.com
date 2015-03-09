<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Create\InvalidValue;

use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;
use SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Create\ServiceTest;

class EmptyWebsiteTest extends ServiceTest {

    public function testCallWithoutSettingUserThrowsException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception',
            'Website cannot be empty',
            JobConfigurationServiceException::CODE_WEBSITE_CANNOT_BE_EMPTY
        );

        $values = new ConfigurationValues();
        $values->setLabel('foo');
        $values->setTaskConfigurationCollection($this->getStandardTaskConfigurationCollection());
        $values->setType($this->getJobTypeService()->getFullSiteType());

        $this->getJobConfigurationService()->setUser($this->getUserService()->getPublicUser());
        $this->getJobConfigurationService()->create($values);
    }

}