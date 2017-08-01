<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Create;

use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;

class WithoutUserTest extends ServiceTest {

    const LABEL = 'foo';

    /**
     * @var JobConfiguration
     */
    private $jobConfiguration = null;

    protected function setUp() {
        parent::setUp();


    }

    public function testCallWithoutSettingUserThrowsException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception',
            'User is not set',
            JobConfigurationServiceException::CODE_USER_NOT_SET
        );

        $this->jobConfiguration = $this->getJobConfigurationService()->create(new ConfigurationValues());
    }

}