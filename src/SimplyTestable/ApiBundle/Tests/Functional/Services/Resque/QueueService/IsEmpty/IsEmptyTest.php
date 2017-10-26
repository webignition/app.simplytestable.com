<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Resque\QueueService\IsEmpty;

use SimplyTestable\ApiBundle\Tests\Functional\Services\Resque\QueueService\ServiceTest as BaseServiceTest;

class IsEmptyTest extends BaseServiceTest {

    public function testIsEmpty() {
        $this->assertTrue($this->getService()->isEmpty('tasks-notify'));
    }

}
