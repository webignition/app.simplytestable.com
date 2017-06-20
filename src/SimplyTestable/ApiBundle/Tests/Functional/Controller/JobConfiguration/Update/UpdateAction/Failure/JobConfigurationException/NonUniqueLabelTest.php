<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Update\UpdateAction\Failure\JobConfigurationException;

use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;

class NonUniqueLabelTest extends ExceptionTest {

    protected function getHeaderErrorCode()
    {
        return JobConfigurationServiceException::CODE_LABEL_NOT_UNIQUE;
    }

    protected function getHeaderErrorMessage()
    {
        return 'Label "bar" is not unique';
    }

    protected function getNewLabel()
    {
        return self::LABEL2;
    }

    protected function getNewParameters()
    {
        return $this->getOriginalParameters() . '-foo';
    }

    protected function getMethodLabel()
    {
        return self::LABEL1;
    }
}