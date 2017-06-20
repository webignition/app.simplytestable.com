<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Resque\Job\Worker\Tasks\Notify;

use SimplyTestable\ApiBundle\Tests\Functional\Resque\Job\CommandJobTest as BaseJobTest;

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
