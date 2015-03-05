<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Delete\InvalidLabel;

use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Delete\ServiceTest;

abstract class InvalidLabelTest extends ServiceTest {

    const LABEL = 'bar';

    abstract protected function getCurrentUser();


    public function testInvalidLabelThrowsException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception',
            'Configuration with label "bar" does not exist',
            JobConfigurationServiceException::CODE_NO_SUCH_CONFIGURATION
        );

        $this->getJobConfigurationService()->setUser($this->getCurrentUser());
        $this->getJobConfigurationService()->delete(self::LABEL);
    }

}