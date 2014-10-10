<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Resque\QueueService\Contains\DoesContain;

class NoArgsTest extends ServiceTest {

    protected function getCreateQueueName() {
        return 'tasks-notify';
    }
}
