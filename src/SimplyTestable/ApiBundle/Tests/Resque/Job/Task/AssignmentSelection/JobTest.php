<?php

namespace SimplyTestable\ApiBundle\Tests\Resque\Job\Task\AssignmentSelection;

use SimplyTestable\ApiBundle\Tests\Resque\Job\CommandJobTest as BaseJobTest;

class JobTest extends BaseJobTest {

    protected function getArgs() {
        return [];
    }


    protected function getExpectedQueue() {
        return 'task-assignment-selection';
    }

    protected function getJobCommandClass() {
        return 'SimplyTestable\\ApiBundle\\Command\\Task\\AssignmentSelectionCommand';
    }

}
