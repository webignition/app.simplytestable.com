<?php

namespace SimplyTestable\ApiBundle\Tests\Resque\Job\Job\Prepare;

use SimplyTestable\ApiBundle\Tests\Resque\Job\JobTest as BaseJobTest;

class JobTest extends BaseJobTest {

    protected function getArgs() {
        return [
            'id' => 1
        ];
    }


    protected function getExpectedQueue() {
        return 'job-prepare';
    }

}
