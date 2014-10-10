<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Resque\QueueService\Contains\DoesContain;

use SimplyTestable\ApiBundle\Tests\Services\Resque\QueueService\Contains\ServiceTest as BaseServiceTest;

abstract class ServiceTest extends BaseServiceTest {

    protected function getExpectedDoesContain() {
        return true;
    }

    protected function getContainsQueueName() {
        return $this->getCreateQueueName();
    }

}
