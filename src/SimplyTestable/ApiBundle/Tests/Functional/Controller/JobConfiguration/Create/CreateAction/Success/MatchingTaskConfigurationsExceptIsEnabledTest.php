<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Create\CreateAction\Success;

class MatchingTaskConfigurationsExceptIsEnabledTest extends SuccessTest {

    protected function preCallController() {
        $requestPostData = $this->getRequestPostData();
        $requestPostData['label'] = 'bar';

        $requestPostData['task-configuration']['HTML validation']['is-enabled'] = false;

        $methodName = $this->getActionNameFromRouter();
        $this->getCurrentController($requestPostData)->$methodName(
            $this->container->get('request')
        );
    }


    protected function getLabel() {
        return 'foo';
    }

    /**
     * @return bool
     */
    protected function getExpectedTaskConfigurationIsEnabled()
    {
        return true;
    }

}