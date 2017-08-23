<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Resque\QueueService\Contains\DoesNotContain;

class ArgsTest extends ServiceTest {

    private $args = [
        'ids' => ['1,2,3'],
    ];

    protected function getCreateQueueName() {
        return 'task-assign-collection';
    }

    protected function getContainsQueueName() {
        return 'task-assign-collection';
    }

    protected function getCreateArgs() {
        return $this->args;
    }

    protected function getContainsArgs() {
        return ['1,2,4'];
    }

}
