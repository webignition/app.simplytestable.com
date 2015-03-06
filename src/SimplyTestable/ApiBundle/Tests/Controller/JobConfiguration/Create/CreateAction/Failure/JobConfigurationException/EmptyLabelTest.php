<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Create\CreateAction\Failure\JobConfigurationException;

use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;

class EmptyLabelTest extends ExceptionTest {

    protected function getRequestPostData() {
        $postData = parent::getRequestPostData();
        $postData['label'] = '';

        return $postData;
    }


    protected function getHeaderErrorCode()
    {
        return JobConfigurationServiceException::CODE_LABEL_CANNOT_BE_EMPTY;
    }

    protected function getHeaderErrorMessage()
    {
        return 'Label cannot be empty';
    }

}