<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Resque\Job\Job\Resolve;

use SimplyTestable\ApiBundle\Tests\Functional\Resque\Job\CommandJobTest as BaseJobTest;

class JobTest extends BaseJobTest {

    protected function getArgs() {
        return [
            'id' => 1
        ];
    }


    protected function getExpectedQueue() {
        return 'job-resolve';
    }


    protected function getJobCommandClass() {
        return 'SimplyTestable\\ApiBundle\\Command\\Job\\ResolveWebsiteCommand';
    }

}