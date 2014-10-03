<?php

namespace SimplyTestable\ApiBundle\Tests\Resque\Job\Task\Assign;

use SimplyTestable\ApiBundle\Tests\Resque\Job\JobTest as BaseJobTest;

class JobFirstSetTest extends BaseJobTest {

    protected function getArgs() {
        return [
            'id' => 1
        ];
    }


    protected function getExpectedQueue() {
        return 'task-assign';
    }

}
