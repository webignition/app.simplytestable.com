<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Create\CreateAction\Failure\JobConfigurationException;

use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;

class EmptyTaskCollectionTest extends ExceptionTest {

    protected function getRequestPostData() {
        $postData = parent::getRequestPostData();
        $postData['task-configuration'] = '';

        return $postData;
    }


    protected function getHeaderErrorCode()
    {
        return JobConfigurationServiceException::CODE_TASK_CONFIGURATION_COLLECTION_IS_EMPTY;
    }

    protected function getHeaderErrorMessage()
    {
        return 'TaskConfigurationCollection is empty';
    }

}