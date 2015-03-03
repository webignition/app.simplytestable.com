<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Create;

use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

class WithoutUserTest extends ServiceTest {

    const LABEL = 'foo';

    /**
     * @var JobConfiguration
     */
    private $jobConfiguration = null;

    public function setUp() {
        parent::setUp();


    }

    public function testCallWithoutSettingUserThrowsException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception',
            'User is not set',
            JobConfigurationServiceException::CODE_USER_NOT_SET
        );

        $this->jobConfiguration = $this->getJobConfigurationService()->create(
            $this->getWebSiteService()->fetch('http://example.com/'),
            $this->getJobTypeService()->getFullSiteType(),
            [],
            self::LABEL,
            ''
        );
    }

}