<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\QueueService;

use SimplyTestable\ApiBundle\Tests\Services\ServiceTest as BaseServiceTest;

abstract class ServiceTest extends BaseServiceTest {

    /**
     * @return \SimplyTestable\ApiBundle\Services\Task\QueueService
     */
    protected function getService() {
        return parent::getService();
    }

}
