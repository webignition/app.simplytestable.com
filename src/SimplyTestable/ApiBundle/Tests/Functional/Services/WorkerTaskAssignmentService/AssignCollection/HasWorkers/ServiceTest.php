<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\WorkerTaskAssignmentService\AssignCollection\HasWorkers;

use SimplyTestable\ApiBundle\Tests\Functional\Services\WorkerTaskAssignmentService\AssignCollection\ServiceTest as BaseServiceTest;

abstract class ServiceTest extends BaseServiceTest {

    abstract protected function getWorkerCount();

}
