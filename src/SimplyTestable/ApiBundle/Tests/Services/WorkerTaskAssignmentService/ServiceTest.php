<?php

namespace SimplyTestable\ApiBundle\Tests\Services\WorkerTaskAssignmentService;

use SimplyTestable\ApiBundle\Tests\Services\ServiceTest as BaseServiceTest;

abstract class ServiceTest extends BaseServiceTest {

    /**
     * @return \SimplyTestable\ApiBundle\Services\WorkerTaskAssignmentService
     */
    protected function getService() {
        return parent::getService();
    }

}
