<?php

namespace SimplyTestable\ApiBundle\Tests\Resque\Job\Task\AssignCollection;

use SimplyTestable\ApiBundle\Tests\Resque\Job\CommandJobTest as BaseJobTest;

abstract class JobTest extends BaseJobTest {

    protected function getJobCommandClass() {
        return 'SimplyTestable\\ApiBundle\\Command\\Task\\Assign\\CollectionCommand';
    }

}
