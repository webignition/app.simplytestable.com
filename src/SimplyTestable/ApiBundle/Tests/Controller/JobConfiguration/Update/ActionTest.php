<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Update;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;
use SimplyTestable\ApiBundle\Services\Job\ConfigurationService as JobConfigurationService;

abstract class ActionTest extends BaseControllerJsonTestCase {

    const LABEL = 'foo';
    const NEW_LABEL = 'foo-new';

    public function setUp() {
        parent::setUp();
        $this->getRouter()->getContext()->setMethod('POST');
    }


    /**
     * @return JobConfigurationService
     */
    protected function getJobConfigurationService() {
        return $this->container->get('simplytestable.services.job.configurationservice');
    }



    /**
     * @param array $postData
     * @param array $queryData
     * @return \SimplyTestable\ApiBundle\Controller\JobConfiguration\CreateController
     */
    protected function getCurrentController(array $postData = [], array $queryData = []) {
        return parent::getCurrentController($postData, $queryData);
    }


    /**
     *
     * @return array
     */
    protected function getRouteParameters() {
        return [
            'label' => 'foo'
        ];
    }


    protected function getRequestPostData() {
        return [
            'website' => 'http://example.com/',
            'type' => 'Full site',
            'task-configuration' => [
                'HTML validation' => [],
                'CSS validation' => []
            ],
            'parameters' => '',
            'label' => self::NEW_LABEL
        ];
    }

}