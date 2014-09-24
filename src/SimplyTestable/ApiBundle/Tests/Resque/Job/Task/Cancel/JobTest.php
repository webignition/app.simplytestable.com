<?php

namespace SimplyTestable\ApiBundle\Tests\Resque\Job\Task\Cancel;

use SimplyTestable\ApiBundle\Tests\Resque\Job\JobTest as BaseJobTest;

class JobTest extends BaseJobTest {

    protected function getArgs() {
        return [
            'id' => 1
        ];
    }


    protected function getExpectedQueue() {
        return 'task-cancel';
    }

}
