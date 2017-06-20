<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Resque\QueueService;

use SimplyTestable\ApiBundle\Tests\Functional\Services\ServiceTest as BaseServiceTest;

abstract class ServiceTest extends BaseServiceTest {

    /**
     * @return \SimplyTestable\ApiBundle\Services\Resque\QueueService
     */
    protected function getService() {
        return parent::getService();
    }

}
