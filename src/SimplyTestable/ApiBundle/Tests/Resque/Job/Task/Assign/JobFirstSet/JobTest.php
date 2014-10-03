<?php

namespace SimplyTestable\ApiBundle\Tests\Resque\Job\Task\Assign\JobFirstSet;

use SimplyTestable\ApiBundle\Tests\Resque\Job\CommandJobTest as BaseJobTest;

class JobTest extends BaseJobTest {

    protected function getArgs() {
        return [
            'id' => 1
        ];
    }


    protected function getExpectedQueue() {
        return 'task-assign-jobfirstset';
    }


    protected function getJobCommandClass() {
        return 'SimplyTestable\\ApiBundle\\Command\\Task\\Assign\\JobFirstSetCommand';
    }

}
