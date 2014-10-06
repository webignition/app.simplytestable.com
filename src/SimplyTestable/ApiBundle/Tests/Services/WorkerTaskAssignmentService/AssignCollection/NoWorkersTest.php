<?php

namespace SimplyTestable\ApiBundle\Tests\Services\WorkerTaskAssignmentService\AssignCollection;

use SimplyTestable\ApiBundle\Services\WorkerTaskAssignmentService;

class NoWorkersTest extends ServiceTest {

    protected function getExpectedReturnCode() {
        return WorkerTaskAssignmentService::ASSIGN_COLLECTION_NO_WORKERS_STATUS_CODE;
    }

    protected function getTasks() {
        return [];
    }

    protected function getWorkers() {
        return [];
    }

}
