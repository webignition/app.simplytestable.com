<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Resque\QueueService\Contains\DoesNotContain;

use SimplyTestable\ApiBundle\Tests\Services\Resque\QueueService\Contains\ServiceTest as BaseServiceTest;

abstract class ServiceTest extends BaseServiceTest {

    protected function getExpectedDoesContain() {
        return false;
    }

}
