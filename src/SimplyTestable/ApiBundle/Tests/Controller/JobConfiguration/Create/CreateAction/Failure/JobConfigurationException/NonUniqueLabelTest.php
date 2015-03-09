<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Create\CreateAction\Failure\JobConfigurationException;

use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;

class NonUniqueLabelTest extends ExceptionTest {

    protected function preCallController() {
        $methodName = $this->getActionNameFromRouter();
        $this->getCurrentController($this->getRequestPostData())->$methodName();
    }


    protected function getHeaderErrorCode()
    {
        return JobConfigurationServiceException::CODE_LABEL_NOT_UNIQUE;
    }

    protected function getHeaderErrorMessage()
    {
        return 'Label "foo" is not unique';
    }

}