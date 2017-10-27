<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\UserPostActivationProperties;

use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Services\UserPostActivationPropertiesService;

abstract class ServiceTest extends AbstractBaseTestCase {

    /**
     * @return UserPostActivationPropertiesService
     */
    protected function getUserPostActivationPropertiesService() {
        return $this->container->get('simplytestable.services.job.UserPostActivationPropertiesService');
    }


}
