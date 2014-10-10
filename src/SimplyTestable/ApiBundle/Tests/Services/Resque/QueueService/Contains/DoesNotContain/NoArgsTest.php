<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Resque\QueueService\Contains\DoesNotContain;

class NoArgsTest extends ServiceTest {

    protected function getCreateQueueName() {
        return 'tasks-notify';
    }


    protected function getContainsQueueName() {
        return 'task-assign-collection';
    }

}
