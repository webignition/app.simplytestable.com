<?php

namespace Tests\ApiBundle\Functional\Services\UserPostActivationProperties;

use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Services\UserPostActivationPropertiesService;

abstract class ServiceTest extends AbstractBaseTestCase {

    /**
     * @return UserPostActivationPropertiesService
     */
    protected function getUserPostActivationPropertiesService() {
        return $this->container->get('simplytestable.services.job.UserPostActivationPropertiesService');
    }


}
