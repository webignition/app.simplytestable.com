<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Update\UpdateAction\Failure\JobConfigurationException;

use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;

class NonUniqueJobConfigurationTest extends ExceptionTest {

    protected function getHeaderErrorCode()
    {
        return JobConfigurationServiceException::CODE_CONFIGURATION_ALREADY_EXISTS;
    }

    protected function getHeaderErrorMessage()
    {
        return 'Matching configuration already exists';
    }

    protected function getNewLabel()
    {
        return self::LABEL . '-foo';
    }

    protected function getNewParameters()
    {
        return $this->getOriginalParameters();
    }

    protected function getMethodLabel()
    {
        return self::LABEL2;
    }
}