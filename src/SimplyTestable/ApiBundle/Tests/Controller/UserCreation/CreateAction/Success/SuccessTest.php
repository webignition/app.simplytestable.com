<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\UserCreation\CreateAction\Success;

use SimplyTestable\ApiBundle\Tests\Controller\UserCreation\CreateAction\ActionTest;
use SimplyTestable\ApiBundle\Services\UserPostActivationPropertiesService;

abstract class SuccessTest extends ActionTest {

    /**
     * @return UserPostActivationPropertiesService
     */
    protected function getUserPostActivationPropertiesService() {
        return $this->container->get('simplytestable.services.job.UserPostActivationPropertiesService');
    }

}

