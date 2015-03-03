<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

abstract class ServiceTest extends BaseSimplyTestableTestCase {

    /**
     * @return \SimplyTestable\ApiBundle\Services\Job\ConfigurationService
     */
    protected function getJobConfigurationService() {
        return $this->container->get('simplytestable.services.job.configurationservice');
    }

}
