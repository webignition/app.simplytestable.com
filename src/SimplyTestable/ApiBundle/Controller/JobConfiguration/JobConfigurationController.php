<?php

namespace SimplyTestable\ApiBundle\Controller\JobConfiguration;

use SimplyTestable\ApiBundle\Controller\ApiController;
use SimplyTestable\ApiBundle\Services\Job\ConfigurationService as JobConfigurationService;

abstract class JobConfigurationController extends ApiController {

    /**
     * @return JobConfigurationService
     */
    protected function getJobConfigurationService() {
        return $this->get('simplytestable.services.job.configurationservice');
    }


}
