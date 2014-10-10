<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Resque\QueueService\Contains\DoesContain;

class PartialArgsTest extends ServiceTest {

    private $args = [
        'ids' => '1,2,3',
        'worker' => 'foo.example.com'
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
        return [
            'worker' => 'foo.example.com'
        ];
    }

}
