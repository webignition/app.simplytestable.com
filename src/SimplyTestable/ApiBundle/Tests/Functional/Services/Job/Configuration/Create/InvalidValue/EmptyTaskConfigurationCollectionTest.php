<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Create\InvalidValue;

use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;
use SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Create\ServiceTest;

class EmptyTaskConfigurationCollectionTest extends ServiceTest {

    public function testCallWithoutSettingUserThrowsException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception',
            'TaskConfigurationCollection is empty',
            JobConfigurationServiceException::CODE_TASK_CONFIGURATION_COLLECTION_IS_EMPTY
        );

        $values = new ConfigurationValues();
        $values->setLabel('foo');
        $values->setType($this->getJobTypeService()->getFullSiteType());
        $values->setWebsite($this->getWebSiteService()->fetch('http://example.com/'));

        $this->getJobConfigurationService()->setUser($this->getUserService()->getPublicUser());
        $this->getJobConfigurationService()->create($values);
    }

}