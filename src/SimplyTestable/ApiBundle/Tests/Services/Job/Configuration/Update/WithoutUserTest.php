<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Update;

use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;

class WithoutUserTest extends ServiceTest {

    const LABEL = 'foo';

    public function testCallWithoutSettingUserThrowsException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception',
            'User is not set',
            JobConfigurationServiceException::CODE_USER_NOT_SET
        );

        $this->getJobConfigurationService()->update(
            self::LABEL,
            $this->getWebSiteService()->fetch('http://example.com/'),
            $this->getJobTypeService()->getFullSiteType(),
            $this->getStandardTaskConfigurationCollection(),
            ''
        );
    }

}