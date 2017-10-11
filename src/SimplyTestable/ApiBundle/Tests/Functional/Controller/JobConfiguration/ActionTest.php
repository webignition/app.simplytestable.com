<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;
use SimplyTestable\ApiBundle\Services\Job\ConfigurationService as JobConfigurationService;

abstract class ActionTest extends BaseControllerJsonTestCase
{
    /**
     * @return JobConfigurationService
     */
    protected function getJobConfigurationService()
    {
        return $this->container->get('simplytestable.services.job.configurationservice');
    }
}
