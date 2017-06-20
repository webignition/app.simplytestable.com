<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Resque\QueueService\Contains\DoesNotContain;

use SimplyTestable\ApiBundle\Tests\Functional\Services\Resque\QueueService\Contains\ServiceTest as BaseServiceTest;

abstract class ServiceTest extends BaseServiceTest {

    protected function getExpectedDoesContain() {
        return false;
    }

}
