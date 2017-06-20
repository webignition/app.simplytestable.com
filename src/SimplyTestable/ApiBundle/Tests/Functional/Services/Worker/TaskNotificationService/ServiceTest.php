<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Worker\TaskNotificationService;

use SimplyTestable\ApiBundle\Tests\Functional\Services\ServiceTest as BaseServiceTest;

abstract class ServiceTest extends BaseServiceTest {

    /**
     * @return \SimplyTestable\ApiBundle\Services\Worker\TaskNotificationService
     */
    protected function getService() {
        return parent::getService();
    }

}
