<?php

namespace SimplyTestable\ApiBundle\Tests\Resque\Job\Task\AssignCollection;

use SimplyTestable\ApiBundle\Tests\Resque\Job\JobTest as BaseJobTest;

class WithWorkerTest extends BaseJobTest {

    protected function getArgs() {
        return [
            'ids' => '1,2,3',
            'worker' => 'foo'
        ];
    }


    protected function getExpectedQueue() {
        return 'task-assign-collection';
    }

}
