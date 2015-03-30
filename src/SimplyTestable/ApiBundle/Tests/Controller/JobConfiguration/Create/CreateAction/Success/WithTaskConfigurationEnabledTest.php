<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Create\CreateAction\Success;

class WithTaskConfigurationEnabledTest extends SuccessTest {

    protected function getLabel() {
        return 'foo';
    }

    protected function getRequestPostData() {
        $requestPostData = parent::getRequestPostData();

        foreach ($requestPostData['task-configuration'] as $taskKey => $taskConfigurationDetails) {
            $taskConfigurationDetails['is-enabled'] = true;
            $requestPostData['task-configuration'][$taskKey] = $taskConfigurationDetails;
        }

        return $requestPostData;
    }

    /**
     * @return bool
     */
    protected function getExpectedTaskConfigurationIsEnabled()
    {
        return true;
    }

}