<?php

namespace SimplyTestable\ApiBundle\Tests\Resque\Job\Task\AssignCollection;

use SimplyTestable\ApiBundle\Tests\Resque\Job\JobTest as BaseJobTest;

class JobTest extends BaseJobTest {

    protected function getArgs() {
        return [
            'ids' => '1,2,3'
        ];
    }


    protected function getExpectedQueue() {
        return 'task-assign-collection';
    }

}
