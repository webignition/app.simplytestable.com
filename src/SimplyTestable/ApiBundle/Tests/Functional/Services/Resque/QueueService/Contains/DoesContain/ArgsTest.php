<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Resque\QueueService\Contains\DoesContain;

class ArgsTest extends ServiceTest {

    private $args = [
        'ids' => ['1,2,3'],
    ];

    protected function getCreateQueueName() {
        return 'task-assign-collection';
    }

    protected function getCreateArgs() {
        return $this->args;
    }

    protected function getContainsArgs() {
        return $this->getCreateArgs();
    }

}
