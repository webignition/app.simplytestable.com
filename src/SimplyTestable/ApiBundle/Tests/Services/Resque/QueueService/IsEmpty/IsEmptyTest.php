<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Resque\QueueService\IsEmpty;

use SimplyTestable\ApiBundle\Tests\Services\Resque\QueueService\ServiceTest as BaseServiceTest;

class IsEmptyTest extends BaseServiceTest {

    public function setUp() {
        parent::setUp();
        $this->clearRedis();
    }

    public function testIsEmpty() {
        $this->assertTrue($this->getService()->isEmpty('tasks-notify'));
    }

}
