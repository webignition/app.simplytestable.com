<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Worker\TaskNotificationService;

use SimplyTestable\ApiBundle\Tests\Services\ServiceTest as BaseServiceTest;

abstract class ServiceTest extends BaseServiceTest {

    /**
     * @return \SimplyTestable\ApiBundle\Services\Worker\TaskNotificationService
     */
    protected function getService() {
        return parent::getService();
    }

}
