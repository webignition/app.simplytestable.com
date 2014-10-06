<?php

namespace SimplyTestable\ApiBundle\Tests\Services\WorkerTaskAssignmentService\AssignCollection;

use SimplyTestable\ApiBundle\Tests\Services\WorkerTaskAssignmentService\ServiceTest as BaseServiceTest;

abstract class ServiceTest extends BaseServiceTest {

    abstract protected function getExpectedReturnCode();
    abstract protected function getTasks();
    abstract protected function getWorkers();

    public function testReturnCode() {
        $this->assertEquals($this->getExpectedReturnCode(), $this->getService()->assignCollection($this->getTasks(), $this->getWorkers()));
    }

}
