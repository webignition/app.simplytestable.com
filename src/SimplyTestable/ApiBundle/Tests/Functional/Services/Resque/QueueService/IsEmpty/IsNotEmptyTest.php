<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Resque\QueueService\IsEmpty;

use SimplyTestable\ApiBundle\Tests\Functional\Services\Resque\QueueService\ServiceTest as BaseServiceTest;

class IsNotEmptyTest extends BaseServiceTest {

    protected function setUp() {
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
