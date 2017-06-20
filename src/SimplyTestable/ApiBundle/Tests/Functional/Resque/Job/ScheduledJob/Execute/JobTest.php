<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Resque\Job\ScheduledJob\Execute;

use SimplyTestable\ApiBundle\Tests\Functional\Resque\Job\CommandJobTest as BaseJobTest;

class JobTest extends BaseJobTest {

    protected function getArgs() {
        return [
            'id' => 1
        ];
    }


    protected function getExpectedQueue() {
        return 'scheduledjob-execute';
    }


    protected function getJobCommandClass() {
        return 'SimplyTestable\\ApiBundle\\Command\\ScheduledJob\\ExecuteCommand';
    }

}
