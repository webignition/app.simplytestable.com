<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Resque\QueueService\Contains\DoesContain;

use SimplyTestable\ApiBundle\Tests\Functional\Services\Resque\QueueService\Contains\ServiceTest as BaseServiceTest;

abstract class ServiceTest extends BaseServiceTest {

    protected function getExpectedDoesContain() {
        return true;
    }

    protected function getContainsQueueName() {
        return $this->getCreateQueueName();
    }

}