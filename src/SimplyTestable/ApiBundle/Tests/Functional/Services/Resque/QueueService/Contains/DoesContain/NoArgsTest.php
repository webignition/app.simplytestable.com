<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Resque\QueueService\Contains\DoesContain;

class NoArgsTest extends ServiceTest {

    protected function getCreateQueueName() {
        return 'tasks-notify';
    }
}
