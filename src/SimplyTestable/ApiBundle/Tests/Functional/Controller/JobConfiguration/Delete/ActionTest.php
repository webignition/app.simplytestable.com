<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Delete;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;
use SimplyTestable\ApiBundle\Services\Job\ConfigurationService as JobConfigurationService;

abstract class ActionTest extends BaseControllerJsonTestCase {


    /**
     * @return JobConfigurationService
     */
    protected function getJobConfigurationService() {
        return $this->container->get('simplytestable.services.job.configurationservice');
    }


    /**
     * @return \SimplyTestable\ApiBundle\Services\ScheduledJob\Service
     */
    protected function getScheduledJobService() {
        return $this->container->get('simplytestable.services.scheduledjob.service');
    }



    /**
     * @param array $postData
     * @param array $queryData
     * @return \SimplyTestable\ApiBundle\Controller\JobConfiguration\CreateController
     */
    protected function getCurrentController(array $postData = [], array $queryData = []) {
        return parent::getCurrentController($postData, $queryData);
    }

}