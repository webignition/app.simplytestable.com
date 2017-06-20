<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Resque\Job\Task\AssignCollection;

use SimplyTestable\ApiBundle\Tests\Functional\Resque\Job\CommandJobTest as BaseJobTest;

abstract class JobTest extends BaseJobTest {

    protected function getJobCommandClass() {
        return 'SimplyTestable\\ApiBundle\\Command\\Task\\Assign\\CollectionCommand';
    }

}
