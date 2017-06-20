<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Update;

use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

class WithoutUserTest extends ServiceTest {

    const LABEL = 'foo';

    public function testCallWithoutSettingUserThrowsException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception',
            'User is not set',
            JobConfigurationServiceException::CODE_USER_NOT_SET
        );

        $this->getJobConfigurationService()->update(new JobConfiguration(), new ConfigurationValues());
    }

}