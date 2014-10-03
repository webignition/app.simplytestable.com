<?php

namespace SimplyTestable\ApiBundle\Tests\Resque\Job\Worker\ActivateVerify;

use SimplyTestable\ApiBundle\Tests\Resque\Job\CommandJobTest as BaseJobTest;

class JobTest extends BaseJobTest {

    protected function getArgs() {
        return [
            'id' => 1
        ];
    }


    protected function getExpectedQueue() {
        return 'worker-activate-verify';
    }


    protected function getJobCommandClass() {
        return 'SimplyTestable\\ApiBundle\\Command\\WorkerActivateVerifyCommand';
    }

}
