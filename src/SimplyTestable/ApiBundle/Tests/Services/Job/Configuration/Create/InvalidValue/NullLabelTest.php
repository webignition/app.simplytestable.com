<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Create\InvalidValue;

use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;
use SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Create\ServiceTest;

class NullLabelTest extends ServiceTest {

    public function testCallWithoutSettingUserThrowsException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception',
            'Label cannot be empty',
            JobConfigurationServiceException::CODE_LABEL_CANNOT_BE_EMPTY
        );

        $values = new ConfigurationValues();
        $values->setLabel(null);
        $values->setTaskConfigurationCollection($this->getStandardTaskConfigurationCollection());
        $values->setType($this->getJobTypeService()->getFullSiteType());
        $values->setWebsite($this->getWebSiteService()->fetch('http://example.com/'));

        $this->getJobConfigurationService()->setUser($this->getUserService()->getPublicUser());
        $this->getJobConfigurationService()->create($values);
    }

}