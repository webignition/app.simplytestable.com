<?php

namespace SimplyTestable\ApiBundle\Tests\Resque\Job\Task\CancelCollection;

use SimplyTestable\ApiBundle\Tests\Resque\Job\CommandJobTest as BaseJobTest;

class JobTest extends BaseJobTest {

    protected function getArgs() {
        return [
            'ids' => '1,2,3'
        ];
    }


    protected function getExpectedQueue() {
        return 'task-cancel-collection';
    }


    protected function getJobCommandClass() {
        return 'SimplyTestable\\ApiBundle\\Command\\Task\\Cancel\\CollectionCommand';
    }

}
