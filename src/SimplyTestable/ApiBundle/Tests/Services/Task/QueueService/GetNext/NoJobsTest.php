<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\QueueService\GetNext;

use SimplyTestable\ApiBundle\Tests\Services\Task\QueueService\ServiceTest;

class NoJobsTest extends ServiceTest {

    public function testGetsEmptySet() {
        $this->assertEquals([], $this->getService()->getNext());
    }

}
