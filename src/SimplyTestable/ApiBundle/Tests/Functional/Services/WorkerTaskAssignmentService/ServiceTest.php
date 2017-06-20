<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\WorkerTaskAssignmentService;

use SimplyTestable\ApiBundle\Tests\Functional\Services\ServiceTest as BaseServiceTest;

abstract class ServiceTest extends BaseServiceTest {

    /**
     * @return \SimplyTestable\ApiBundle\Services\WorkerTaskAssignmentService
     */
    protected function getService() {
        return parent::getService();
    }

}
