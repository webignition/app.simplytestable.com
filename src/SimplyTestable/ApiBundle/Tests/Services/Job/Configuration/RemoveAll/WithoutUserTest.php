<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\RemoveAll;

use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;

class WithoutUserTest extends ServiceTest {

    public function testCallWithoutSettingUserThrowsException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception',
            'User is not set',
            JobConfigurationServiceException::CODE_USER_NOT_SET
        );

        $this->getJobConfigurationService()->removeAll();
    }

}