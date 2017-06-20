<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Create\CreateAction\Failure\JobConfigurationException;

use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;

class NonUniqueJobConfigurationTest extends ExceptionTest {

    protected function preCallController() {
        $requestPostData = $this->getRequestPostData();
        $requestPostData['label'] = 'bar';

        $methodName = $this->getActionNameFromRouter();
        $this->getCurrentController($requestPostData)->$methodName(
            $this->container->get('request')
        );
    }


    protected function getHeaderErrorCode()
    {
        return JobConfigurationServiceException::CODE_CONFIGURATION_ALREADY_EXISTS;
    }

    protected function getHeaderErrorMessage()
    {
        return 'Matching configuration already exists';
    }

}