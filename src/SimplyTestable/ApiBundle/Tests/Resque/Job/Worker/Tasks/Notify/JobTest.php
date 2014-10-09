<?php

namespace SimplyTestable\ApiBundle\Tests\Resque\Job\Worker\Tasks\Notify;

use SimplyTestable\ApiBundle\Tests\Resque\Job\CommandJobTest as BaseJobTest;

class JobTest extends BaseJobTest {

    protected function getArgs() {
        return [];
    }


    protected function getExpectedQueue() {
        return 'tasks-notify';
    }


    protected function getJobCommandClass() {
        return 'SimplyTestable\\ApiBundle\\Command\\Worker\TaskNotificationCommand';
    }

}
