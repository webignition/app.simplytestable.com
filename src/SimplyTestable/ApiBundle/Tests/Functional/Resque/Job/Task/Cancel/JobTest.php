<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Resque\Job\Task\Cancel;

use SimplyTestable\ApiBundle\Tests\Functional\Resque\Job\CommandJobTest as BaseJobTest;

class JobTest extends BaseJobTest {

    protected function getArgs() {
        return [
            'id' => 1
        ];
    }


    protected function getExpectedQueue() {
        return 'task-cancel';
    }


    protected function getJobCommandClass() {
        return 'SimplyTestable\\ApiBundle\\Command\\Task\\Cancel\\Command';
    }

}
