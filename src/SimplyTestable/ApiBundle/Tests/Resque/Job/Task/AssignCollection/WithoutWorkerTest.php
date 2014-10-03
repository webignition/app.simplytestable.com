<?php

namespace SimplyTestable\ApiBundle\Tests\Resque\Job\Task\AssignCollection;

class WithoutWorkerTest extends JobTest {

    protected function getArgs() {
        return [
            'ids' => '1,2,3'
        ];
    }


    protected function getExpectedQueue() {
        return 'task-assign-collection';
    }

    protected function getJobCommandClass() {
        return 'SimplyTestable\\ApiBundle\\Command\\Task\\Assign\\CollectionCommand';
    }

}
