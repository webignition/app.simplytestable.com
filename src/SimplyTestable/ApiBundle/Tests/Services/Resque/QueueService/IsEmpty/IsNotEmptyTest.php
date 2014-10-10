<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Resque\QueueService\IsEmpty;

use SimplyTestable\ApiBundle\Tests\Services\Resque\QueueService\ServiceTest as BaseServiceTest;

class IsNotEmptyTest extends BaseServiceTest {

    public function setUp() {
        parent::setUp();
        $this->clearRedis();

        $this->getResqueQueueService()->enqueue(
            $this->getResqueJobFactoryService()->create(
                'tasks-notify'
            )
        );
    }

    public function testIsEmpty() {
        $this->assertFalse($this->getService()->isEmpty('tasks-notify'));
    }

}
