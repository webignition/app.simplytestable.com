<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Create\CreateAction\Success;

class WithTaskConfigurationNotEnabledTest extends SuccessTest {

    protected function getLabel() {
        return 'foo';
    }

    protected function getRequestPostData() {
        $requestPostData = parent::getRequestPostData();

        foreach ($requestPostData['task-configuration'] as $taskKey => $taskConfigurationDetails) {
            $taskConfigurationDetails['is-enabled'] = false;
            $requestPostData['task-configuration'][$taskKey] = $taskConfigurationDetails;
        }

        return $requestPostData;
    }

    /**
     * @return bool
     */
    protected function getExpectedTaskConfigurationIsEnabled()
    {
        return false;
    }

}