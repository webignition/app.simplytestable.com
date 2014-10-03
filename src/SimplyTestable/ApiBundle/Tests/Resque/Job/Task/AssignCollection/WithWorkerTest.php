<?php

namespace SimplyTestable\ApiBundle\Tests\Resque\Job\Task\AssignCollection;

class WithWorkerTest extends JobTest {

    protected function getArgs() {
        return [
            'ids' => '1,2,3',
            'worker' => 'foo'
        ];
    }


    protected function getExpectedQueue() {
        return 'task-assign-collection';
    }


    protected function getJobCommandClass() {
        return 'SimplyTestable\\ApiBundle\\Command\\Task\\Assign\\CollectionCommand';
    }

}
