<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Resque\QueueService;

use SimplyTestable\ApiBundle\Tests\Services\ServiceTest as BaseServiceTest;

abstract class ServiceTest extends BaseServiceTest {

    /**
     * @return \SimplyTestable\ApiBundle\Services\Resque\QueueService
     */
    protected function getService() {
        return parent::getService();
    }

}
