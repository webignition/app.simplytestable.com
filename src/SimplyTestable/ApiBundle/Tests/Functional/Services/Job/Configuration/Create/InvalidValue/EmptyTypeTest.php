<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Create\InvalidValue;

use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;
use SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Create\ServiceTest;

class EmptyTypeTest extends ServiceTest {

    public function testCallWithoutSettingUserThrowsException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception',
            'Type cannot be empty',
            JobConfigurationServiceException::CODE_TYPE_CANNOT_BE_EMPTY
        );

        $values = new ConfigurationValues();
        $values->setLabel('foo');
        $values->setTaskConfigurationCollection($this->getStandardTaskConfigurationCollection());
        $values->setWebsite($this->getWebSiteService()->fetch('http://example.com/'));

        $this->getJobConfigurationService()->setUser($this->getUserService()->getPublicUser());
        $this->getJobConfigurationService()->create($values);
    }

}