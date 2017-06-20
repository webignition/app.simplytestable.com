<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\UserPostActivationProperties;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Services\UserPostActivationPropertiesService;

abstract class ServiceTest extends BaseSimplyTestableTestCase {

    /**
     * @return UserPostActivationPropertiesService
     */
    protected function getUserPostActivationPropertiesService() {
        return $this->container->get('simplytestable.services.job.UserPostActivationPropertiesService');
    }


}
